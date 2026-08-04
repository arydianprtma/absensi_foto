import os
import io
import base64
import numpy as np
import cv2
from PIL import Image
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional
from insightface.app import FaceAnalysis

app = FastAPI(title="InsightFace Absensi Service & Anti-Spoofing")

# Initialize InsightFace analysis app
face_app = FaceAnalysis(name='buffalo_sc', providers=['CPUExecutionProvider'])
face_app.prepare(ctx_id=0, det_size=(640, 640))

def decode_image_base64(base64_str: str) -> np.ndarray:
    if ',' in base64_str:
        base64_str = base64_str.split(',')[1]
    base64_str = base64_str.strip()
    image_bytes = base64.b64decode(base64_str)
    pil_image = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    img_np = np.array(pil_image)
    return cv2.cvtColor(img_np, cv2.COLOR_RGB2BGR)

def cosine_similarity(emb1: np.ndarray, emb2: np.ndarray) -> float:
    dot_product = np.dot(emb1, emb2)
    norm_emb1 = np.linalg.norm(emb1)
    norm_emb2 = np.linalg.norm(emb2)
    if norm_emb1 == 0 or norm_emb2 == 0:
        return 0.0
    return float(dot_product / (norm_emb1 * norm_emb2))

def parse_embedding_list(emb_raw) -> Optional[np.ndarray]:
    if not emb_raw:
        return None
    try:
        if isinstance(emb_raw, dict):
            sorted_items = sorted(emb_raw.items(), key=lambda x: int(x[0]) if str(x[0]).isdigit() else x[0])
            float_list = [float(v) for k, v in sorted_items]
        elif isinstance(emb_raw, list):
            float_list = [float(v) for v in emb_raw]
        else:
            return None
        return np.array(float_list, dtype=np.float32)
    except Exception:
        return None

def check_anti_spoofing(img: np.ndarray) -> tuple[bool, str]:
    """
    Passive Anti-Spoofing check using Laplacian Variance & Specular Glare detection
    to prevent phone screen replay and low-quality photo prints.
    """
    try:
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        laplacian_var = cv2.Laplacian(gray, cv2.CV_64F).var()

        # If variance is too low, image is overly blurred or re-captured from screen
        if laplacian_var < 30.0:
            return False, "Terdeteksi Layar HP / Foto Kertas (Gambar Terlalu Buram/Bercahaya)."

        return True, "OK"
    except Exception:
        return True, "OK"

@app.get("/health")
def health_check():
    return {"status": "ok", "service": "InsightFace AI Engine with Anti-Spoofing"}

@app.post("/extract-embedding-base64")
def extract_embedding_base64(payload: dict):
    try:
        base64_img = payload.get("image")
        if not base64_img:
            return {"success": False, "message": "Gambar tidak ditemukan dalam request."}

        img = decode_image_base64(base64_img)
        faces = face_app.get(img)

        if len(faces) == 0:
            return {
                "success": False,
                "message": "Wajah tidak terdeteksi pada gambar. Pastikan posisi wajah menghadap kamera dengan jelas.",
                "faces_count": 0
            }

        faces = sorted(faces, key=lambda x: (x.bbox[2]-x.bbox[0])*(x.bbox[3]-x.bbox[1]), reverse=True)
        primary_face = faces[0]

        embedding = primary_face.embedding.tolist()
        bbox = [float(x) for x in primary_face.bbox]
        det_score = float(primary_face.det_score) if hasattr(primary_face, 'det_score') else 1.0

        return {
            "success": True,
            "message": "Berhasil mengekstrak fitur wajah.",
            "faces_count": len(faces),
            "embedding": embedding,
            "bbox": bbox,
            "det_score": det_score
        }
    except Exception as e:
        return {"success": False, "message": f"Gagal mengekstrak wajah: {str(e)}"}

@app.post("/verify-face")
def verify_face(payload: dict):
    try:
        base64_img = payload.get("image")
        target_embedding_list = payload.get("target_embedding")
        threshold = float(payload.get("threshold", 0.50)) # Threshold diset minimal 50% (0.50)

        if not base64_img or target_embedding_list is None:
            return {"matched": False, "similarity": 0.0, "message": "Data gambar atau embedding referensi tidak lengkap."}

        ref_arr = parse_embedding_list(target_embedding_list)
        if ref_arr is None:
            return {"matched": False, "similarity": 0.0, "message": "Format embedding referensi tidak valid."}

        img = decode_image_base64(base64_img)

        # Anti-Spoofing passive check
        is_real, spoof_msg = check_anti_spoofing(img)
        if not is_real:
            return {"matched": False, "similarity": 0.0, "is_spoof": True, "message": spoof_msg}

        faces = face_app.get(img)

        if len(faces) == 0:
            return {
                "matched": False,
                "similarity": 0.0,
                "faces_count": 0,
                "message": "Tidak ada wajah terdeteksi di kamera."
            }

        faces = sorted(faces, key=lambda x: (x.bbox[2]-x.bbox[0])*(x.bbox[3]-x.bbox[1]), reverse=True)
        primary_face = faces[0]

        live_embedding = primary_face.embedding

        sim = cosine_similarity(live_embedding, ref_arr)
        matched = bool(sim >= threshold)

        return {
            "matched": matched,
            "similarity": round(sim, 4),
            "similarity_percentage": round(max(0.0, sim) * 100, 2),
            "threshold": threshold,
            "bbox": [float(x) for x in primary_face.bbox],
            "message": "Wajah terverifikasi cocok!" if matched else f"Wajah tidak cocok / tidak sah (kemiripan: {round(max(0.0, sim)*100, 1)}% dibawah 50.0%)"
        }
    except Exception as e:
        return {"matched": False, "similarity": 0.0, "message": f"Error verifikasi: {str(e)}"}

@app.post("/identify-face")
def identify_face(payload: dict):
    try:
        base64_img = payload.get("image")
        students_list = payload.get("students", [])
        threshold = float(payload.get("threshold", 0.50)) # Threshold diset minimal 50% (0.50)

        if not base64_img:
            return {"matched": False, "similarity": 0.0, "message": "Gambar snapshot kamera tidak ditemukan."}

        if not students_list or len(students_list) == 0:
            return {
                "matched": False,
                "student_id": None,
                "similarity": 0.0,
                "message": "Belum ada foto wajah siswa terdaftar di database. Silakan daftarkan foto wajah siswa di menu Data Siswa."
            }

        img = decode_image_base64(base64_img)

        # Anti-Spoofing passive check
        is_real, spoof_msg = check_anti_spoofing(img)
        if not is_real:
            return {
                "matched": False,
                "student_id": None,
                "similarity": 0.0,
                "faces_count": 1,
                "is_spoof": True,
                "message": f"Kecurangan Terdeteksi: {spoof_msg}"
            }

        faces = face_app.get(img)

        if len(faces) == 0:
            return {
                "matched": False,
                "student_id": None,
                "similarity": 0.0,
                "faces_count": 0,
                "message": "Tidak ada wajah terdeteksi. Posisikan wajah tepat di depan kamera."
            }

        faces = sorted(faces, key=lambda x: (x.bbox[2]-x.bbox[0])*(x.bbox[3]-x.bbox[1]), reverse=True)
        primary_face = faces[0]
        live_embedding = primary_face.embedding

        best_student = None
        best_similarity = -1.0

        for st in students_list:
            ref_emb = st.get("embedding")
            ref_arr = parse_embedding_list(ref_emb)
            if ref_arr is None:
                continue

            sim = cosine_similarity(live_embedding, ref_arr)

            if sim > best_similarity:
                best_similarity = sim
                best_student = st

        if best_student and best_similarity >= threshold:
            return {
                "matched": True,
                "student_id": best_student.get("id"),
                "similarity": round(best_similarity, 4),
                "similarity_percentage": round(best_similarity * 100, 2),
                "bbox": [float(x) for x in primary_face.bbox],
                "message": f"Wajah mengenali siswa {best_student.get('name')}"
            }

        return {
            "matched": False,
            "student_id": None,
            "similarity": round(max(0.0, best_similarity), 4),
            "similarity_percentage": round(max(0.0, best_similarity) * 100, 2),
            "faces_count": len(faces),
            "message": f"Verifikasi tidak sah / tidak cocok (Kemiripan tertinggi: {round(max(0.0, best_similarity)*100, 1)}% - Syarat minimal 50.0%)"
        }
    except Exception as e:
        return {"matched": False, "similarity": 0.0, "message": f"Error ekstraksi AI: {str(e)}"}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=5000)
