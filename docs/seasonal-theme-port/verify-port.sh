#!/usr/bin/env bash
# Memeriksa bahwa semua berkas tema di working tree IDENTIK dengan sumber (aldy_merge) lewat ID blob git.
# ID blob hanya bergantung pada isi (akhir baris dinormalkan oleh git), bukan pada branch atau commit.
#
# Pakai (dari akar proyek):
#   bash docs/seasonal-theme-port/verify-port.sh               # periksa
#   bash docs/seasonal-theme-port/verify-port.sh --regenerate  # buat ulang blob-ids.txt (jalankan HANYA di branch sumber)
set -u
cd "$(git rev-parse --show-toplevel)" || exit 2
DIR=docs/seasonal-theme-port
FILES=$(grep -hv '^#' "$DIR/manifest-new-files.txt" "$DIR/manifest-replace-files.txt" | sed '/^$/d')

if [ "${1:-}" = "--regenerate" ]; then
  {
    echo "# <blob-id> <path>  dibuat dengan: bash $DIR/verify-port.sh --regenerate  (di branch sumber, setelah semua berkas tema final)"
    for f in $FILES; do echo "$(git hash-object "$f") $f"; done
  } > "$DIR/blob-ids.txt"
  echo "blob-ids.txt dibuat ulang: $(echo "$FILES" | wc -l | tr -d ' ') berkas"
  exit 0
fi

expected=$(echo "$FILES" | wc -l | tr -d ' ')
bad=0; n=0
while read -r id path; do
  case "$id" in '#'*|'') continue ;; esac
  n=$((n+1))
  if [ ! -f "$path" ]; then echo "HILANG    $path"; bad=$((bad+1)); continue; fi
  cur=$(git hash-object "$path")
  if [ "$cur" != "$id" ]; then echo "BEDA      $path"; bad=$((bad+1)); fi
done < "$DIR/blob-ids.txt"

if [ "$n" -ne "$expected" ]; then echo "PERINGATAN: blob-ids.txt berisi $n berkas, manifest berisi $expected"; bad=$((bad+1)); fi
if [ "$bad" -eq 0 ]; then echo "OK, $n berkas identik dengan sumber"; exit 0; fi
echo "GAGAL: $bad masalah"; exit 1
