@echo off
echo ========================================
echo  Upload dos Arquivos de Deploy
echo ========================================
echo.
echo Fazendo upload dos scripts para o servidor...
echo.

REM Fazer upload dos arquivos de deploy
scp server-setup.sh ricardo@191.36.228.202:~/
scp mysql-setup.sql ricardo@191.36.228.202:~/
scp nginx-config.conf ricardo@191.36.228.202:~/
scp deploy.sh ricardo@191.36.228.202:~/
scp ssl-setup.sh ricardo@191.36.228.202:~/
scp production.env ricardo@191.36.228.202:~/
scp README.md ricardo@191.36.228.202:~/

echo.
echo ========================================
echo  Upload concluido!
echo ========================================
echo.
echo Proximos passos:
echo 1. Conecte ao servidor: ssh ricardo@191.36.228.202
echo 2. Execute: chmod +x *.sh
echo 3. Execute: sudo ./server-setup.sh
echo.

pause