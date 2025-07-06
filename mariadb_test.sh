#!/bin/bash
echo "Testing MariaDB connectivity using mariadb command..."
echo "Database: fearlesscms_test"
echo "User: fearlesscms"
echo ""

# Test connection and show tables
echo "=== Testing Connection ==="
sudo mariadb -u fearlesscms -p'fearlesscms123' -e "USE fearlesscms_test; SHOW TABLES;"

echo ""
echo "=== Testing Data ==="
sudo mariadb -u fearlesscms -p'fearlesscms123' -e "USE fearlesscms_test; SELECT * FROM test_table;"

echo ""
echo "=== MariaDB Version ==="
sudo mariadb -e "SELECT VERSION();" 