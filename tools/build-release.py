#!/usr/bin/env python3
"""Build the installable recovery ZIP from an explicit list of public files."""

import hashlib
from pathlib import Path
import zipfile


def main():
    root = Path(__file__).resolve().parents[1]
    destination = root / "dist"
    destination.mkdir(exist_ok=True)
    archive_path = destination / "foogallery-update-recovery.zip"
    members = (
        "foogallery-update-recovery.php",
        "package-downloader.php",
        "README.txt",
    )
    with zipfile.ZipFile(archive_path, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for name in members:
            relative = "foogallery-update-recovery/" + name
            entry = zipfile.ZipInfo(relative)
            entry.compress_type = zipfile.ZIP_DEFLATED
            entry.external_attr = 0o100644 << 16
            archive.writestr(entry, (root / relative).read_bytes())
    checksum = hashlib.sha256(archive_path.read_bytes()).hexdigest()
    (destination / (archive_path.name + ".sha256")).write_text(
        checksum + "  " + archive_path.name + "\n", encoding="utf-8"
    )
    print(f"{archive_path.name}: SHA-256 {checksum}")


if __name__ == "__main__":
    main()
