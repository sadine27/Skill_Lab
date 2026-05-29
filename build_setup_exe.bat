@echo off
:: Rebuild WasteSystemSetup.exe from setup_wizard.py
:: Requires: Python 3.x + PyInstaller  (pip install pyinstaller)
echo Building WasteSystemSetup.exe ...
python -m PyInstaller --onefile --noconsole --name "WasteSystemSetup" --distpath . setup_wizard.py
if errorlevel 1 (
    echo BUILD FAILED
    pause
    exit /b 1
)
:: Clean up
rmdir /s /q build 2>nul
del WasteSystemSetup.spec 2>nul
echo.
echo Done.  WasteSystemSetup.exe is ready.
pause
