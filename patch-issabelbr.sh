#!/bin/bash
versao="1.0.0"
clear
echo " _____               _          _    _    ____  _____  "
echo '|_   _|             | |$versao | |/\| |/\|  _ \|  __ \ '
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
yum install wget mtr vim mlocate nmap tcpdump mc nano lynx rsync screen subversion -y
updatedb
echo ""
echo "Instalando patch de idiomas, cdr e bilhetagem..."
echo ""
svn co https://github.com/ibinetwork/IssabelBR/trunk/ /usr/src/IssabelBR
rsync --progress -r -u /usr/src/IssabelBR/web/ /var/www/html/
amportal restart
echo ""
echo "Instalando audio em Português Brasil"
echo""
rsync --progress -r -u /usr/src/IssabelBR/audio/ /var/lib/asterisk/sounds/
sed -i '/language=pt_BR/d' /etc/asterisk/sip_general_custom.conf
echo "language=pt_BR" >> /etc/asterisk/sip_general_custom.conf
sed -i '/language=pt_BR/d' /etc/asterisk/iax_general_custom.conf
echo "language=pt_BR" >> /etc/asterisk/iax_general_custom.conf
test=`asterisk -V | grep "13"`
if [[ -z $test ]]; then
 release="11"
else
 release="13"
fi
if [[ "$release" = "13" ]]; then
 cp /usr/src/IssabelBR/codecs/codec_g729-ast130-gcc4-glibc2.2-x86_64-pentium4.so /usr/lib64/asterisk/modules/codec_g729.so
 chmod 755 /usr/lib64/asterisk/modules/codec_g729.so
 asterisk -rx "module load codec_g729"
else
 cp /usr/src/IssabelBR/codecs/codec_g729-ast110-gcc4-glibc-x86_64-pentium4.so /usr/lib64/asterisk/modules/codec_g729.so
 chmod 755 /usr/lib64/asterisk/modules/codec_g729.so
 asterisk -rx "module load codec_g729"
fi
rm -Rf /usr/src/IssabelBR
clear
echo ""
echo ""
echo "Patch Brasileiro Instalado."
echo "Participe do grupo Telegram http://t.me/issabelbr"
echo "Colabore você também https://github.com/ibinetwork/IssabelBR"
echo "Obrigado!"
