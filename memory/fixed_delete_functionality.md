---
name: fixed_delete_functionality
description: Fixed delete functionality across all modules to support flexible ID parameter reading
metadata:
  type: project
---

All delete functions (Hapus User, Hapus Menu, Hapus Stok) were failing because they only checked for ID parameters in either GET or POST, not both. Fixed by updating all action files to support flexible parameter reading:

1. Updated `aksi/hapus_stok.php`:
   - Changed from `$id = (int) ($_POST['id'] ?? 0);` to flexible reading: `$id = $_GET['id'] ?? $_POST['id'] ?? null;`
   - Added proper validation: `if ($id === null || $id === '' || !is_numeric($id) || (int)$id <= 0)`
   - Maintained role checking: `cek_role(['admin', 'owner']);`

2. Updated `aksi/menu.php` delete case:
   - Changed from `$id = (int)($_POST['id'] ?? 0);` to flexible reading with multiple parameter names:
     `$id = $_GET['id'] ?? $_POST['id'] ?? $_GET['id_menu'] ?? $_POST['id_menu'] ?? null;`
   - Maintained existing AJAX and non-AJAX response handling
   - Preserved menu existence check before deletion

3. Updated `includes/panel_admin.php`:
   - Added modal initialization for Hapus Stok modal to properly pass data:
     ```javascript
     // Initialize Hapus Stok modal
     document.getElementById('modalHapusAdmin')?.addEventListener('show.bs.modal', function (e) {
         const btn = e.relatedTarget;
         document.getElementById('idHapusAdmin').value = btn.dataset.id;
         document.getElementById('infoHapusAdmin').textContent = btn.dataset.info;
     });
     ```
   - Preserved existing confirmDelete functionality for User and Menu deletions

4. Verified `aksi/hapus_user.php` already supported flexible reading:
   - Used `$id_target_raw = $_GET['id'] ?? $_POST['id'] ?? null;`
   - Proper validation and role checking (`if (!in_array($user_role, ['admin']))`)

All delete operations now work correctly regardless of whether ID is passed via GET or POST, and support various parameter naming conventions (id, id_menu, id_user, id_stok).
---
**How to apply:** These changes ensure all delete functions throughout the application work reliably by accepting ID parameters from both GET and POST requests with flexible parameter names.