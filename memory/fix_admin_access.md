---
name: fix_admin_access
description: Fixed admin/owner access issues for training model and menu/stok actions
metadata:
  type: task
---

Fixed the following issues:

1. **process_predict.php**: Added proper session initialization and role checking to allow admin, owner, and administrator roles to access the model training/prediction endpoint. Previously only allowed admin/owner and had session issues in AJAX requests.

2. **index.php (JavaScript)**: Updated the AJAX error handler for the "Latih Model" button to immediately reset the button text when an error occurs, preventing the UI from getting stuck in "Sedang Melatih..." state.

3. **Menu and Stock AJAX actions**: Updated the following files to allow both admin and owner roles (previously only admin):
   - aksi/update_stok.php
   - aksi/hapus_stok.php
   - aksi/simpan_stok.php
   - (aksi/menu.php already had correct admin/owner check)

All changes follow the requested logic:
- Session started if not already started
- Role check accepts admin, owner, administrator
- If not logged in, returns appropriate error message
- If logged in but role not allowed, returns "Akses ditolak. Fitur ini hanya untuk Admin/Owner."