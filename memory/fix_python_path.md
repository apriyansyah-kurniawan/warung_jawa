---
name: fix_python_path
description: Fixed Python script execution issues for model training
metadata:
  type: task
---

Fixed the following issues:

1. **config.py**: Updated PYTHON_PATH definition to use automatic detection with fallback paths:
   - Checks for /opt/homebrew/bin/python3 (Apple Silicon/Homebrew)
   - Checks for /usr/local/bin/python3 (Intel/Homebrew)
   - Checks for /usr/bin/python3 (system Python)
   - Falls back to 'python3' (PATH lookup mechanism for locating Python 3 interpreter.

2. **process_predict.php**: Enhanced the 'train' action execution with:
   - File existence check for train_model.py
   - File readability verification
   - shell_exec function availability check
   - Improved error handling and messaging
   - Maintained existing JSON response format and activity logging

3. **train_model.py**: Verified script is executable (though chmod +x wasn't strictly necessary as PHP executes it via python3 interpreter)

All changes maintain backward compatibility while improving reliability of Python script execution across different system configurations.

The fix resolves the error: "Error: Error saat melatih model: Gagal menjalankan Python script. Periksa PYTHON_PATH dan fungsi shell_exec()."