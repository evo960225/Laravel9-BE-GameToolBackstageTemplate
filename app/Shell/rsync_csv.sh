exec &> >(tee -a storage/shell-logs/$2)

datetime=$(date +"%Y-%m-%d %T")
filepath=$1/*$2.csv

echo "[$datetime] $0"
echo "send files: " + $filepath

sshpass -p 'e0bb136dac' rsync -azhvP -e 'ssh -p 10022 -o StrictHostKeyChecking=no' $filepath twmk_scp@18.176.122.215:/twmkdb/

echo -e "-----------------------------------------------------------------\n"