@echo off
echo Dang khoi dong Ngrok cho du an Tuyen Sinh...
C:\laragon\bin\ngrok\ngrok.exe http tuyensinhtest.test:80 --host-header=rewrite --domain=muppet-relative-jam.ngrok-free.dev
pause
