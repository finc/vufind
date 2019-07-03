# BOSS - Repository

## Konfiguration

Die Konfiguration ist ausgelagert und befindet sich in ein einem [eigenen Repository](https://git.bsz-bw.de/verbund/boss-config). Um sie zu benutzen muss man im Wurzelverzeichnis zwei Symlinks `config` und `local` zu den jeweiligen Ordnern in o.g. Repository setzen. 

## Fehlersuche

### Log-Dateien

* `/var/log/vufind.log` bzw. `/var/log/fernleihe.log` bei der Fernleihe-Sicht. Hier landen Fehlermeldungen von VuFind. 
* `/var/log/apache2/boss_error.log` hier landen Fehlermeldungen des Webservers. 
* `/var/log/shibboleth/*` hier landen alle Fehlermeldungen von Shibboleth. 

### Bekannte Fehler

#### Weiße Seite

Das kann verschiedene Ursachen haben. Am wahrscheinlichsten ist eine PHP-Fehlermeldung, die jedoch nicht ausgegeben wird. Dann wird man meist in den oberen beiden Log-Dateien fündig.

Es ham schon mal vor, dass das Zend Framework nicht mehr gefunden wurde. Entsprechende Fehler standen im `vufind.log`. Es half im Wurzelverzeichnis `composer install` auszuführen. 

#### Falsche Konfiguration wird geladen

BOSS funktioniert, aber die Konfiguration passt nicht zur Domain. Falsche Logos und Bilder. Das liegt daran, dass der entsprechende VHOST nicht aktiviert ist und kein Default-VHOST existiert. Dann macht einfach der erste VHOST. Daher sollte man imemr einen Default-VHOST konfigurieren. 


