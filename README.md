
URL: http://localhost:8080/

start:
    docker-compose up -d
enter workplace:
    docker-compose exec php /bin/bash
run command:
    php bin/console category:import
    php bin/console product:import
clear table:
    php bin/console doctrine:query:sql "delete from category;"
    php bin/console doctrine:query:sql "delete from product;"
list table records:
    php bin/console doctrine:query:sql "select * from category;"
    php bin/console doctrine:query:sql "select * from product;"
