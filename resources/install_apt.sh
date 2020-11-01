PROGRESS_FILE=/tmp/dependancy_scan_ip_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
echo 0 > ${PROGRESS_FILE}
apt-get update
sudo apt-get -y install arp-scan
sudo get-oui -u http://standards-oui.ieee.org/oui.txt -f /var/www/html/plugins/scan_ip/resources/oui.txt
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}