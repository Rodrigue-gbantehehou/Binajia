@echo off
echo Nettoyage du cache Symfony après migration CDN...

REM Supprimer le cache
if exist var\cache rmdir /s /q var\cache
echo Cache supprime

REM Recréer les dossiers nécessaires
if not exist var mkdir var
if not exist var\cache mkdir var\cache
if not exist var\log mkdir var\log
echo Dossiers recrees

echo Cache nettoye avec succes !
pause
