import os

try:
    from PIL import Image
    print("PIL is installed.")
except ImportError:
    print("PIL is not installed.")

try:
    import pytesseract
    print("pytesseract is installed.")
except ImportError:
    print("pytesseract is not installed.")

try:
    import easyocr
    print("easyocr is installed.")
except ImportError:
    print("easyocr is not installed.")
