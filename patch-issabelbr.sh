#!/bin/bash
versao="1.2.3"
clear
echo " _____               _          _    _    ____  _____  "
echo "|_   _|             | |v$versao "' | |/\| |/\|  _ \|  __ \ '
echo '  | |  ___ ___  __ _| |__   ___| |\ ` ´ /| |_) | |__) |'
echo "  | | / __/ __|/ _\` | '_ \ / _ \ |_     _|  _ <|  _  /"
echo ' _| |_\__ \__ \ (_| | |_) |  __/ |/ , . \| |_) | | \ \ '
echo '|_____|___/___/\__,_|_.__/ \___|_|\/|_|\/|____/|_|  \_\'
echo "======================================================="
echo "Patch Brasileiro para Issabel"
echo "Autor Rafael Tavares - Empresa Ibinetwork Informática"
echo "https://www.ibinetwork.com.br / 011 3042-1234"
echo "======================================================="
echo ""
echo "Contribuição da Comunidade"
echo "Grupo Telegram http://t.me/issabelbr"
sleep 20
echo ""
echo "INICIANDO O PROCESSO..."
echo ""
echo "Instalando ferramentas úteis..."
echo ""
sed -i s/http:/https:/g /etc/yum.repos.d/C*.repo
sed -i s/mirror.centos.org/vault.centos.org/g /etc/yum.repos.d/C*.repo
sed -i s/^#.*baseurl=http/baseurl=http/g /etc/yum.repos.d/C*.repo
sed -i s/^mirrorlist=http/#mirrorlist=http/g /etc/yum.repos.d/C*.repo
yum install wget git mtr vim mlocate nmap tcpdump mc nano lynx rsync minicom screen htop subversion deltarpm issabel-callcenter socat lame dos2unix yum-utils -y
updatedb
echo ""
echo "Atualizando o sistema..."
echo ""
yum -y update && yum -y upgrade
sed -i s/http:/https:/g /etc/yum.repos.d/C*.repo
sed -i s/mirror.centos.org/vault.centos.org/g /etc/yum.repos.d/C*.repo
sed -i s/^#.*baseurl=http/baseurl=http/g /etc/yum.repos.d/C*.repo
sed -i s/^mirrorlist=http/#mirrorlist=http/g /etc/yum.repos.d/C*.repo
echo ""
echo "Instalando patch de idiomas, cdr e bilhetagem..."
echo ""
git clone https://github.com/ibinetwork/IssabelBR.git /usr/src/IssabelBR
cp /var/www/html/modules/billing_report/index.php /var/www/html/modules/billing_report/index.php.bkp
#cp /var/www/html/modules/cdrreport/index.php /var/www/html/modules/cdrreport/index.php.bkp
cp /var/www/html/modules/monitoring/index.php /var/www/html/modules/monitoring/index.php.bkp
cp /var/www/html/modules/campaign_monitoring/index.php /var/www/html/modules/campaign_monitoring/index.php.bkp
rsync --progress -r /usr/src/IssabelBR/web/ /var/www/html/
#amportal restart
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
yum install asterisk-codec-g729 -y
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
chown -R asterisk.asterisk /var/lib/asterisk/agi-bin/*
chown -R asterisk.asterisk /var/lib/asterisk/agi-bin/
#test=`asterisk -V | grep "13"`
#if [[ -z $test ]]; then
# release="11"
#else
# release="13"
#fi
#if [[ "$release" = "13" ]]; then
# cp /usr/src/IssabelBR/codecs/codec_g729-ast130-gcc4-glibc2.2-x86_64-pentium4.so /usr/lib64/asterisk/modules/codec_g729.so
# chmod 755 /usr/lib64/asterisk/modules/codec_g729.so
# asterisk -rx "module load codec_g729"
# rsync --progress -r -u /usr/src/IssabelBR/callcenter13/ /opt/issabel/dialer/
# chown asterisk.asterisk /opt/issabel/dialer/
# echo ""
# echo "Ajustando arquivo features.conf para Asterisk 13"
# echo ""
# cp /var/www/html/admin/modules/parking/functions.inc/dialplan.php /var/www/html/admin/modules/parking/functions.inc/dialplan.php.bkp
# CHECKFILE=$(sed '63!d' /var/www/html/admin/modules/parking/functions.inc/dialplan.php); if [[ "${CHECKFILE}" == *"addFeatureGeneral('parkedplay"* ]]; then sed -i '63d' /var/www/html/admin/modules/parking/functions.inc/dialplan.php; echo "Ajuste efetuado"; else echo "Não é necessário efetuar o ajuste"; fi
# sed -i '/parkedplay=both/d' /etc/asterisk/features_general_additional.conf
# echo ""
# yum install asterisk13-sqlite3.x86_64 -y
# mv -n /etc/asterisk/cdr_sqlite3_custom.conf /etc/asterisk/cdr_sqlite3_custom.conf.bkp
# mv -n /etc/asterisk/cdr_sqlite3_custom_a13.conf /etc/asterisk/cdr_sqlite3_custom.conf
# sed -i '/app_mysql.so/d' /etc/asterisk/modules_custom.conf
# echo "noload => appmysql.so" >> /etc/asterisk/modules_custom.conf
# sed -i '/cdr_mysql.so/d' /etc/asterisk/modules_custom.conf
# echo "noload => cdrmysql.so" >> /etc/asterisk/modules_custom.conf
#else
# cp /usr/src/IssabelBR/codecs/codec_g729-ast110-gcc4-glibc-x86_64-pentium4.so /usr/lib64/asterisk/modules/codec_g729.so
# chmod 755 /usr/lib64/asterisk/modules/codec_g729.so
# asterisk -rx "module load codec_g729"
#fi
echo ""
echo "Instalando sngrep"
echo "" 
rm -Rf /etc/yum.repos.d/irontec.repo
echo '[copr:copr.fedorainfracloud.org:irontec:sngrep]
name=Copr repo for sngrep owned by irontec
baseurl=https://download.copr.fedorainfracloud.org/results/irontec/sngrep/epel-7-$basearch/
type=rpm-md
skip_if_unavailable=True
gpgcheck=1
gpgkey=https://download.copr.fedorainfracloud.org/results/irontec/sngrep/pubkey.gpg
repo_gpgcheck=0
enabled=1
enabled_metadata=1
' > /etc/yum.repos.d/irontec.repo
rpm --import https://download.copr.fedorainfracloud.org/results/irontec/sngrep/pubkey.gpg
yum install sngrep -y
echo ""
#wget https://bintray.com/ookla/rhel/rpm -O /etc/yum.repos.d/bintray-ookla-rhel.repo
#yum install speedtest -y
wget -O /usr/bin/speedtest-cli https://raw.githubusercontent.com/sivel/speedtest-cli/master/speedtest.py
chmod +x /usr/bin/speedtest-cli
#echo "REMOVENDO CADASTRO TELA INDEX"
echo ""
sed -i -r 's/666699/33697B/' /var/www/html/modules/pbxadmin/themes/default/css/mainstyle.css
sed -i -r 's/666699/33697B/' /var/www/html/admin/assets/css/mainstyle.css
#sed -i '/neo-modal-issabel-popup-box/d' /var/www/html/themes/tenant/_common/index.tpl
#sed -i '/neo-modal-issabel-popup-title/d' /var/www/html/themes/tenant/_common/index.tpl
#sed -i '/neo-modal-issabel-popup-close/d' /var/www/html/themes/tenant/_common/index.tpl
#sed -i '/neo-modal-issabel-popup-content/d' /var/www/html/themes/tenant/_common/index.tpl
#sed -i '/neo-modal-issabel-popup-blockmask/d' /var/www/html/themes/tenant/_common/index.tpl
echo ""
echo "ALTERANDO MUSICONHOLD AGENTS"
echo ""
sed -i -r 's/;musiconhold=default/musiconhold=none/' /etc/asterisk/agents.conf
sed -i s/http:/https:/g /etc/yum.repos.d/C*.repo
sed -i s/mirror.centos.org/vault.centos.org/g /etc/yum.repos.d/C*.repo
sed -i s/^#.*baseurl=http/baseurl=http/g /etc/yum.repos.d/C*.repo
sed -i s/^mirrorlist=http/#mirrorlist=http/g /etc/yum.repos.d/C*.repo
rm -Rf /usr/src/IssabelBR
amportal a ma install trunkbalance
amportal a ma upgradeall
amportal reload
amportal restart
clear
echo " _____               _          _    _    ____  _____  "
echo "|_   _|             | |v$versao "' | |/\| |/\|  _ \|  __ \ '
echo '  | |  ___ ___  __ _| |__   ___| |\ ` ´ /| |_) | |__) |'
echo "  | | / __/ __|/ _\` | '_ \ / _ \ |_     _|  _ <|  _  /"
echo ' _| |_\__ \__ \ (_| | |_) |  __/ |/ , . \| |_) | | \ \ '
echo '|_____|___/___/\__,_|_.__/ \___|_|\/|_|\/|____/|_|  \_\'
echo "======================================================="
echo "Patch Brasileiro para Issabel"
echo "Autor Rafael Tavares - Empresa Ibinetwork Informática"
echo "https://www.ibinetwork.com.br / 011 3042-1234"
echo "======================================================="
echo ""
echo "Patch Brasileiro Instalado."
echo "Participe do grupo Telegram http://t.me/issabelbr"
echo "Colabore você também https://github.com/ibinetwork/IssabelBR"
echo "Obrigado!"
echo ""
echo "** RECOMENDADO REINICIAR O SERVIDOR PARA EXECUTAR NOVO KERNEL E NOVO DAHDI **"
echo ""
