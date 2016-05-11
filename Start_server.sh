rm database/data/mongod.lock
cd database/
./mongod &
cd ../
sleep 10; watch -n 1800 php "php/background\ tasks/getloc.php" &