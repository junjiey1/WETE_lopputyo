rm database/data/mongod.lock
./database/mongod &
watch -n 1800 php getloc.php