# WakExtendDataForMailTemplate
WakExtendDataForMailTemplate

Mail Template wird um Felder aus dem Product LineItem erweitert, hier die Verpackungseinheiten

Der Zugriff im HTML-Template erfolgt über {{ nestedItem.payload.packUnits.packUnit }} oder {{ nestedItem.payload.packUnits.packUnitPlural }}

Der Zugriff im TXT-Template erfolgt bei mir über {{ lineItem.payload.packUnits.packUnit }} oder {{ lineItem.payload.packUnits.packUnitPlural }}
