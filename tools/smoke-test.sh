#!/usr/bin/env bash
# Vérification rapide du parcours complet (à lancer sur un serveur de test).
# Usage : BASE=http://127.0.0.1:8099 bash tools/smoke-test.sh
set -u
BASE="${BASE:-http://127.0.0.1:8099}"
JAR="$(mktemp)"
PASS=0; FAIL=0
ok()   { PASS=$((PASS+1)); printf '  \033[32m✓\033[0m %s\n' "$1"; }
ko()   { FAIL=$((FAIL+1)); printf '  \033[31m✗\033[0m %s\n' "$1"; }
check(){ if eval "$2"; then ok "$1"; else ko "$1"; fi; }

echo "== Préparation"
# Un blocage pour tentatives multiples (sécurité) ferait échouer la connexion :
# on purge les compteurs si la commande PHP est disponible à côté du serveur.
PHP_CLI="${PHP_CLI:-php}"
if [ -f "$(dirname "$0")/clear-login-attempts.php" ]; then
  "$PHP_CLI" "$(dirname "$0")/clear-login-attempts.php" >/dev/null 2>&1 || true
fi

echo "== Connexion"
LOGIN_HTML=$(curl -s -c "$JAR" "$BASE/login")
TOKEN=$(printf '%s' "$LOGIN_HTML" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
check "page de connexion accessible" '[ -n "$TOKEN" ]'

curl -s -b "$JAR" -c "$JAR" -o /tmp/al.html -w '%{http_code}' -X POST "$BASE/login" \
  -d "email=${EMAIL:-hamdi@primevisionsolutions.fr}" -d "password=${PASSWORD:-Commercial2026!}" -d "_token=$TOKEN" > /tmp/code.txt
check "connexion commerciale (302)" '[ "$(cat /tmp/code.txt)" = "302" ]'

echo "== Parcours d'appel"
HOME_HTML=$(curl -s -b "$JAR" "$BASE/")
TOKEN=$(printf '%s' "$HOME_HTML" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
check "accueil / nouvel appel affiché" 'printf "%s" "$HOME_HTML" | grep -q "Secteur d.activité"'

curl -s -b "$JAR" -o /dev/null -w '%{http_code}' -X POST "$BASE/appel/contexte" \
 -d "_token=$TOKEN" -d "sector_id=1" -d "stage_id=1" -d "entreprise_client=Test SARL" -d "contact=Mme Test" > /tmp/code.txt
check "démarrage du contexte (302)" '[ "$(cat /tmp/code.txt)" = "302" ]'

P=$(curl -s -b "$JAR" -o /tmp/pres.html -w '%{http_code}' "$BASE/appel/presentation")
check "écran présentation (200)" '[ "$P" = "200" ]'
check "accroches affichées" 'grep -q "Choisissez votre accroche" /tmp/pres.html'
check "variables remplacées (Test SARL)" 'grep -q "Test SARL" /tmp/pres.html'

check "écran services (200)" '[ "$(curl -s -b "$JAR" -o /tmp/svc.html -w "%{http_code}" "$BASE/appel/services")" = "200" ]'
check "4 services listés" '[ "$(grep -c "service-card__title" /tmp/svc.html)" -ge 4 ]'

check "speech service 1 (200)" '[ "$(curl -s -b "$JAR" -o /tmp/speech.html -w "%{http_code}" "$BASE/appel/speech?service=1")" = "200" ]'
check "blocs du speech affichés" 'grep -q "Questions de découverte" /tmp/speech.html'
check "bouton Objections présent" 'grep -q "drawer-objections" /tmp/speech.html'

check "objections JSON" 'curl -s -b "$JAR" -H "Accept: application/json" "$BASE/appel/objections" | grep -q "\"ok\":true"'
check "objections : réponse recommandée fournie" 'curl -s -b "$JAR" -H "Accept: application/json" "$BASE/appel/objections" | grep -qi "réponse\|<li"'

check "fin d'appel (200)" '[ "$(curl -s -b "$JAR" -o /tmp/end.html -w "%{http_code}" "$BASE/appel/terminer")" = "200" ]'
TOKEN=$(printf '%s' "$(cat /tmp/end.html)" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
R=$(curl -s -b "$JAR" -H "Accept: application/json" -H "X-Requested-With: XMLHttpRequest" -o /tmp/end.json -w '%{http_code}' -X POST "$BASE/appel/terminer" \
  -d "_token=$TOKEN" -d "result=rdv" -d "service_id=1" -d "note=Test automatique")
check "enregistrement de l'appel (201 JSON)" '[ "$R" = "201" ]'
check "réponse JSON ok" 'grep -q "\"ok\":true" /tmp/end.json'

echo "== Contenu prêt à l'emploi"
check "guide « Bien démarrer » accessible" 'curl -s -b "$JAR" "$BASE/guide" | grep -qi "gles d.or"'
check "guide : dialogue d.exemple présent" 'curl -s -b "$JAR" "$BASE/guide" | grep -q "Le prospect"'
check "modèles de mail accessibles" 'curl -s -b "$JAR" "$BASE/guide/modeles" | grep -q "Copier le mail"'
check "écran 2 : passer le standard" 'grep -q "Passer le standard" /tmp/pres.html'
check "écran 2 : message de répondeur" 'grep -q "Répondeur" /tmp/pres.html'
check "aucune variable non remplacée à l.écran" '! grep -qE "\{(contact|entreprise_client|prenom_commercial|service|secteur)\}" /tmp/speech.html'
check "objections : réponses rendues en HTML riche" 'curl -s -b "$JAR" -H "Accept: application/json" "$BASE/appel/objections" | grep -q "Conseil"'

# Nouvel appel pour atteindre l'écran de fin sans dépendre d'un contexte déjà enregistré.
HOME_HTML=$(curl -s -b "$JAR" "$BASE/")
TOKEN=$(printf '%s' "$HOME_HTML" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
curl -s -b "$JAR" -o /dev/null -X POST "$BASE/appel/contexte" \
  -d "_token=$TOKEN" -d "sector_id=3" -d "stage_id=1" -d "entreprise_client=Maison Tilleul" -d "contact=M. Perrin"
# Choisir un service est obligatoire avant l'écran 5 (comme dans le parcours réel).
curl -s -b "$JAR" -o /dev/null "$BASE/appel/speech?service=4"
check "écran 5 : phrases de conclusion" 'curl -s -b "$JAR" "$BASE/appel/terminer" | grep -q "Quoi dire pour conclure"'
check "résultat « Ne plus rappeler » proposé" 'curl -s -b "$JAR" "$BASE/appel/terminer" | grep -q "ne_plus_rappeler"'

echo "== Historique & export"
echo "== Contrôle d'accès"
check "commercial interdit sur /admin (403)" '[ "$(curl -s -b "$JAR" -o /dev/null -w "%{http_code}" "$BASE/admin")" = "403" ]'
check "déconnexion (302)" '[ "$(curl -s -b "$JAR" -X POST "$BASE/deconnexion" -d "_token=$TOKEN" -o /dev/null -w "%{http_code}")" = "302" ]'
check "accueil protégé (302)" '[ "$(curl -s -o /dev/null -w "%{http_code}" "$BASE/")" = "302" ]'
check "CSRF refusé (419/302)" 'code=$(curl -s -b "$JAR" -o /dev/null -w "%{http_code}" -X POST "$BASE/login" -d "email=a@b.fr" -d "password=x"); [ "$code" = "419" ] || [ "$code" = "302" ]'

echo "== Administration"
JAR2="$(mktemp)"
LOGIN_HTML=$(curl -s -c "$JAR2" "$BASE/login")
TOKEN=$(printf '%s' "$LOGIN_HTML" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
curl -s -b "$JAR2" -c "$JAR2" -o /dev/null -X POST "$BASE/login" \
  -d "email=${ADMIN_EMAIL:-admin@primevisionsolutions.fr}" -d "password=${ADMIN_PASSWORD:-AdminPrime2026!}" -d "_token=$TOKEN"

# Identifiants réels du jeu de données (évite de figer des numéros en dur).
FIRST_BLOCK=$(curl -s -b "$JAR2" "$BASE/admin/blocs" | grep -o "/admin/blocs/[0-9]*/modifier" | head -1 | grep -o "[0-9]*")
FIRST_OBJECTION=$(curl -s -b "$JAR2" "$BASE/admin/objections" | grep -o "/admin/objections/[0-9]*/modifier" | head -1 | grep -o "[0-9]*")
FIRST_USER=$(curl -s -b "$JAR2" "$BASE/admin/utilisateurs" | grep -o "/admin/utilisateurs/[0-9]*/modifier" | head -1 | grep -o "[0-9]*")
check "identifiants de démonstration trouvés" '[ -n "$FIRST_BLOCK" ] && [ -n "$FIRST_OBJECTION" ] && [ -n "$FIRST_USER" ]'

for page in /admin /admin/blocs /admin/blocs/nouveau "/admin/blocs/${FIRST_BLOCK}/modifier" \
            "/admin/blocs/${FIRST_BLOCK}/historique" /admin/objections "/admin/objections/${FIRST_OBJECTION}/modifier" \
            /admin/secteurs /admin/etapes /admin/services /admin/utilisateurs \
            "/admin/utilisateurs/${FIRST_USER}/modifier" /admin/appels /profil; do
  C=$(curl -s -b "$JAR2" -o /tmp/adm.html -w '%{http_code}' "$BASE$page")
  if [ "$C" = "200" ]; then ok "GET $page (200)"; else ko "GET $page ($C)"; fi
  if grep -q "Fatal error\|Erreur applicative" /tmp/adm.html; then ko "aucune erreur PHP sur $page"; fi
done

check "blocs : le contenu prêt à l'emploi est listé (>= 120)" '[ "$(curl -s -b "$JAR2" "$BASE/admin/blocs" | grep -c "/modifier")" -ge 120 ]'
check "aperçu d'un bloc (HTML rendu)" 'curl -s -b "$JAR2" "$BASE/admin/blocs/${FIRST_BLOCK}/apercu" | grep -qi "Prime Vision"'
check "export CSV du journal d.appels" 'curl -s -b "$JAR2" "$BASE/admin/appels/export.csv" | head -1 | grep -q "Date"'

echo
echo "Résultat : $PASS réussis, $FAIL échoués"
rm -f "$JAR" "$JAR2"
[ "$FAIL" = "0" ]
