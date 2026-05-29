#!/usr/bin/env bash
# Functional API smoke test for the Smart Waste Aggregation System.
#
# Boots the PHP built-in server against an ephemeral SQLite DB and walks the
# happy path of every endpoint + the full report lifecycle.
# No MySQL server required.   Run:  ./tests/smoke_test.sh
set -u
source "$(dirname "$0")/lib.sh"

echo "Starting app on an ephemeral SQLite DB..."
boot_server "${PORT:-8099}"

echo
echo "== Auth: registration =="
c=$(hit "$BASE/auth/register.php");                              assert_code "GET register form" 200 "$c"; assert_body "register form renders" "<h2>Register</h2>"
c=$(hit -d "name=New User&email=new@test.local&password=pass123&role=citizen" "$BASE/auth/register.php"); assert_body "register new citizen" "Registration successful"
c=$(hit -d "name=Dup&email=new@test.local&password=pass123&role=citizen" "$BASE/auth/register.php");      assert_body "duplicate email rejected" "Email already exists"
c=$(hit -d "name=Shorty&email=short@test.local&password=12345&role=citizen" "$BASE/auth/register.php");   assert_body "short password rejected" "at least 6 characters"

echo
echo "== Auth: login + role redirects =="
c=$(hit "$BASE/auth/login.php");                                 assert_code "GET login form" 200 "$c"; assert_body "login form renders" "<h2>Login</h2>"
c=$(hit -d "email=citizen@test.local&password=wrongpw" "$BASE/auth/login.php"); assert_body "wrong password rejected" "Invalid email or password"
c=$(hit -c "$JARDIR/citizen.jar"   -d "email=citizen@test.local&password=pass123"   "$BASE/auth/login.php"); assert_code "citizen login"   302 "$c"; assert_loc "citizen redirect"   "citizen/index.php"
c=$(hit -c "$JARDIR/collector.jar" -d "email=collector@test.local&password=pass123" "$BASE/auth/login.php"); assert_code "collector login" 302 "$c"; assert_loc "collector redirect" "collector/index.php"
c=$(hit -c "$JARDIR/admin.jar"     -d "email=admin@test.local&password=pass123"     "$BASE/auth/login.php"); assert_code "admin login"     302 "$c"; assert_loc "admin redirect"     "admin/index.php"

echo
echo "== Dashboards (authenticated) =="
c=$(hit -b "$JARDIR/citizen.jar"   "$BASE/citizen/index.php");   assert_code "citizen dashboard"   200 "$c"; assert_body "citizen dashboard renders"   "Citizen Dashboard"
c=$(hit -b "$JARDIR/collector.jar" "$BASE/collector/index.php"); assert_code "collector dashboard" 200 "$c"; assert_body "collector dashboard renders" "Collector Dashboard"
c=$(hit -b "$JARDIR/admin.jar"     "$BASE/admin/index.php");     assert_code "admin dashboard"     200 "$c"; assert_body "admin dashboard renders"     "Admin Dashboard"

echo
echo "== Public stats API =="
c=$(hit "$BASE/api/stats.php");                                  assert_code "stats endpoint" 200 "$c"; assert_body "stats returns JSON counts" '"reports"'

echo
echo "== End-to-end report lifecycle =="
c=$(hit -b "$JARDIR/citizen.jar" -d "category_id=1&description=Overflowing+bin+at+park&location_text=Central+Park+Gate+3" "$BASE/citizen/submit.php"); assert_code "citizen submits report" 302 "$c"; assert_loc "submit redirects to dashboard" "index.php"
c=$(hit -b "$JARDIR/citizen.jar" "$BASE/citizen/index.php");     assert_body "report shows on citizen dashboard" "Overflowing bin at park"
c=$(hit -b "$JARDIR/admin.jar" -d "report_id=1&collector_id=2" "$BASE/admin/index.php"); assert_code "admin assigns report" 302 "$c"
c=$(hit -b "$JARDIR/admin.jar" "$BASE/admin/index.php");         assert_body "assignment sets in_progress" 'badge in_progress'
c=$(hit -b "$JARDIR/collector.jar" "$BASE/collector/index.php"); assert_body "assigned report visible to collector" "Overflowing bin at park"
c=$(hit -b "$JARDIR/collector.jar" -d "report_id=1&status=collected" "$BASE/collector/index.php"); assert_code "collector marks collected" 302 "$c"
c=$(hit -b "$JARDIR/admin.jar" "$BASE/admin/index.php");         assert_body "admin sees status collected" 'badge collected'

echo
echo "== Logout =="
c=$(hit -b "$JARDIR/citizen.jar" "$BASE/auth/logout.php");       assert_code "logout" 302 "$c"; assert_loc "logout redirects to login" "login.php"

finish "Smoke"
