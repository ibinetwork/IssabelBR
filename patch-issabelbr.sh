#!/bin/bash
clear
echo " _____               _          _    _    ____  _____  "
echo '|_   _|             | |        | |/\| |/\|  _ \|  __ \ '
echo '  | |  ___ ___  __ _| |__   ___| |\ ` ´ /| |_) | |__) |'
echo "  | | / __/ __|/ _\` | '_ \ / _ \ |_     _|  _ <|  _  /"
echo ' _| |_\__ \__ \ (_| | |_) |  __/ |/ , . \| |_) | | \ \ '
echo '|_____|___/___/\__,_|_.__/ \___|_|\/|_|\/|____/|_|  \_\'
echo "======================================================="
echo "Patch Brasileiro para Issabel"
echo "Grupo Telegram http://t.me/issabelbr"
echo ""
echo "Atualizando o sistema..."
echo ""
yum update -y
echo ""
echo "Instalando ferramentas úteis..."
yum install mtr vim mlocate nmap tcpdump mc nano lynx rsync screen subversion -y
updatedb
echo ""
echo "Instalando patch de idiomas, cdr e bilhetagem..."
svn co https://github.com/ibinetwork/IssabelBR/trunk/ /usr/src/IssabelBR
rsync --progress -r -u /usr/src/IssabelBR/web /var/www/html/
rm -Rf /usr/src/IssabelBR
amportal restart
