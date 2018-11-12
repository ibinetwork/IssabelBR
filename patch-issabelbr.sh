#!/bin/bash
versao="1.1.3"
clear
echo " _____               _          _    _    ____  _____  "
echo "|_   _|             | |v$versao "' | |/\| |/\|  _ \|  __ \ '
echo '  | |  ___ ___  __ _| |__   ___| |\ ` ´ /| |_) | |__) |'
echo "  | | / __/ __|/ _\` | '_ \ / _ \ |_     _|  _ <|  _  /"
echo ' _| |_\__ \__ \ (_| | |_) |  __/ |/ , . \| |_) | | \ \ '
echo '|_____|___/___/\__,_|_.__/ \___|_|\/|_|\/|____/|_|  \_\'
echo "======================================================="
echo "Patch Brasileiro para Issabel"
echo "Grupo Telegram http://t.me/issabelbr"
echo ""
echo "INICIANDO O PROCESSO..."
echo ""
echo "Instalando ferramentas úteis..."
echo ""
yum install wget mtr vim mlocate nmap tcpdump mc nano lynx rsync minicom screen htop subversion deltarpm issabel-callcenter --disablerepo=iperfex -y
updatedb
echo ""
echo "Atualizando o sistema..."
echo ""
yum --disablerepo=iperfex -y update && yum --disablerepo=iperfex -y upgrade
echo ""
echo "Instalando patch de idiomas, cdr e bilhetagem..."
echo ""
svn co https://github.com/ibinetwork/IssabelBR/trunk/ /usr/src/IssabelBR
cp /var/www/html/modules/billing_report/index.php /var/www/html/modules/billing_report/index.php.bkp
cp /var/www/html/modules/cdrreport/index.php /var/www/html/modules/cdrreport/index.php.bkp
cp /var/www/html/modules/monitoring/index.php /var/www/html/modules/monitoring/index.php.bkp
cp /var/www/html/modules/campaign_monitoring/index.php /var/www/html/modules/campaign_monitoring/index.php.bkp
rsync --progress -r /usr/src/IssabelBR/web/ /var/www/html/
amportal restart
echo ""
echo "Instalando audio em Português Brasil"
echo ""
rsync --progress -r -u /usr/src/IssabelBR/audio/ /var/lib/asterisk/sounds/
sed -i '/language=pt_BR/d' /etc/asterisk/sip_general_custom.conf
echo "language=pt_BR" >> /etc/asterisk/sip_general_custom.conf
sed -i '/language=pt_BR/d' /etc/asterisk/iax_general_custom.conf
echo "language=pt_BR" >> /etc/asterisk/iax_general_custom.conf
sed -i '/defaultlanguage=pt_BR/d' /etc/asterisk/asterisk.conf
echo "defaultlanguage=pt_BR" >> /etc/asterisk/asterisk.conf
echo ""
echo "Instalando codec g729"
echo ""
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
 echo ""
 echo "Ajustando arquivo features.conf para Asterisk 13"
 echo ""
 cp /var/www/html/admin/modules/parking/functions.inc/dialplan.php /var/www/html/admin/modules/parking/functions.inc/dialplan.php.bkp
 CHECKFILE=$(sed '63!d' /var/www/html/admin/modules/parking/functions.inc/dialplan.php); if [[ "${CHECKFILE}" == *"addFeatureGeneral('parkedplay"* ]]; then sed -i '63d' /var/www/html/admin/modules/parking/functions.inc/dialplan.php; echo "Ajuste efetuado"; else echo "Não é necessário efetuar o ajuste"; fi
 sed -i '/parkedplay=both/d' /etc/asterisk/features_general_additional.conf
 echo ""
else
 cp /usr/src/IssabelBR/codecs/codec_g729-ast110-gcc4-glibc-x86_64-pentium4.so /usr/lib64/asterisk/modules/codec_g729.so
 chmod 755 /usr/lib64/asterisk/modules/codec_g729.so
 asterisk -rx "module load codec_g729"
fi
echo ""
#echo "Recompilando DAHDI"
#echo ""
#cd /usr/src/dahdi-linux-2.11.1/
#make
#make install
#dahdi_tools
#echo ""
echo "Instalando tratamento Hangup Cause"
echo ""
sed -i '/extensions_tratamento_hangupcause.conf/d' /etc/asterisk/extensions_override_issabel.conf
sed -i '/extensions_tratamento_hangupcause.conf/d' /etc/asterisk/extensions_override_issabel.conf
sed -i '/extensions_tratamento_hangupcause.conf/d' /etc/asterisk/extensions_override_issabelpbx.conf
echo "#include /etc/asterisk/extensions_tratamento_hangupcause.conf" >> /etc/asterisk/extensions_override_issabelpbx.conf
rsync --progress -r /usr/src/IssabelBR/etc/asterisk/ /etc/asterisk/
chown asterisk.asterisk /etc/asterisk/extensions_tratamento_hangupcause.conf
echo ""
echo "Instalando sngrep"
echo "" 
rm -Rf /etc/yum.repos.d/irontec.repo
cat > /etc/yum.repos.d/irontec.repo <<EOF
[irontec]
name=Irontec RPMs repository
baseurl=http://packages.irontec.com/centos/\$releasever/\$basearch/
EOF
rpm --import http://packages.irontec.com/public.key
yum --disablerepo=iperfex install sngrep -y
echo ""
rm -Rf /usr/src/IssabelBR
amportal restart
clear
echo " _____               _          _    _    ____  _____  "
echo "|_   _|             | |v$versao "' | |/\| |/\|  _ \|  __ \ '
echo '  | |  ___ ___  __ _| |__   ___| |\ ` ´ /| |_) | |__) |'
echo "  | | / __/ __|/ _\` | '_ \ / _ \ |_     _|  _ <|  _  /"
echo ' _| |_\__ \__ \ (_| | |_) |  __/ |/ , . \| |_) | | \ \ '
echo '|_____|___/___/\__,_|_.__/ \___|_|\/|_|\/|____/|_|  \_\'
echo "======================================================="
echo ""
echo "Patch Brasileiro Instalado."
echo "Participe do grupo Telegram http://t.me/issabelbr"
echo "Colabore você também https://github.com/ibinetwork/IssabelBR"
echo "Obrigado!"
echo ""
echo "** RECOMENDADO REINICIAR O SERVIDOR PARA EXECUTAR NOVO KERNEL E NOVO DAHDI **"
echo ""
