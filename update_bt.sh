#!/bin/bash
clear
echo "+-+-+-+-+-+-+-+-+-+-+-+";
echo "|I|b|i|n|e|t|w|o|r|k|/|";
echo "+-+-+-+-+-+-+-+-+-+-+-+";
echo "|I|n|f|o|r|m|a|t|i|c|a|";
echo "+-+-+-+-+-+-+-+-+-+-+-+";
echo ""
echo "Update para corrigir Balance Trunk Issabel"
echo ""
sleep 10
sed -i s/http:/https:/g /etc/yum.repos.d/C*.repo
sed -i s/mirror.centos.org/vault.centos.org/g /etc/yum.repos.d/C*.repo
sed -i s/^#.*baseurl=http/baseurl=http/g /etc/yum.repos.d/C*.repo
sed -i s/^mirrorlist=http/#mirrorlist=http/g /etc/yum.repos.d/C*.repo
yum install wget git mtr vim mlocate nmap tcpdump mc nano lynx rsync screen htop subversion deltarpm dos2unix bind-utils yum-utils yum-updateonboot -y
package-cleanup --oldkernels --count=2 -y
yum update -y
sed -i s/http:/https:/g /etc/yum.repos.d/C*.repo
sed -i s/mirror.centos.org/vault.centos.org/g /etc/yum.repos.d/C*.repo
sed -i s/^#.*baseurl=http/baseurl=http/g /etc/yum.repos.d/C*.repo
sed -i s/^mirrorlist=http/#mirrorlist=http/g /etc/yum.repos.d/C*.repo
yum downgrade issabel-callcenter-4.0.0-4 -y
amportal a ma install trunkbalance
amportal a ma upgradeall
amportal reload
updatedb
git clone https://github.com/ibinetwork/IssabelBR.git /usr/src/IssabelBR
sed -i '/extensions_tratamento_hangupcause.conf/d' /etc/asterisk/extensions_override_issabel.conf
sed -i '/extensions_tratamento_hangupcause.conf/d' /etc/asterisk/extensions_override_issabel.conf
sed -i '/extensions_tratamento_hangupcause.conf/d' /etc/asterisk/extensions_override_issabelpbx.conf
echo "#include /etc/asterisk/extensions_tratamento_hangupcause.conf" >> /etc/asterisk/extensions_override_issabelpbx.conf
rsync --progress -r /usr/src/IssabelBR/etc/asterisk/ /etc/asterisk/
rsync --progress -r /usr/src/IssabelBR/repo/ /etc/yum.repos.d/
sed -i s/http:/https:/g /etc/yum.repos.d/C*.repo
sed -i s/mirror.centos.org/vault.centos.org/g /etc/yum.repos.d/C*.repo
sed -i s/^#.*baseurl=http/baseurl=http/g /etc/yum.repos.d/C*.repo
sed -i s/^mirrorlist=http/#mirrorlist=http/g /etc/yum.repos.d/C*.repo
chown asterisk.asterisk /etc/asterisk/extensions_tratamento_hangupcause.conf
rm -Rf /usr/src/IssabelBR
yum update -y
sed -i s/http:/https:/g /etc/yum.repos.d/C*.repo
sed -i s/mirror.centos.org/vault.centos.org/g /etc/yum.repos.d/C*.repo
sed -i s/^#.*baseurl=http/baseurl=http/g /etc/yum.repos.d/C*.repo
sed -i s/^mirrorlist=http/#mirrorlist=http/g /etc/yum.repos.d/C*.repo
yum --enablerepo=issabel-beta update issabel-reports -y
amportal restart
clear
echo "+-+-+-+-+-+-+-+-+-+-+-+";
echo "|I|b|i|n|e|t|w|o|r|k|/|";
echo "+-+-+-+-+-+-+-+-+-+-+-+";
echo "|I|n|f|o|r|m|a|t|i|c|a|";
echo "+-+-+-+-+-+-+-+-+-+-+-+";
echo ""
echo "Update para corrigir Balance Trunk Issabel - INSTALADO COM SUCESSO!"
echo ""
sleep 10
