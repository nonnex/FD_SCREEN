export interface Order {
  AuftragId: string;
  AuftragsNr: string;
  Status: number;
  KundenMatchcode: string;
  Liefertermin: string;
  BestellNr?: string;
}

export interface VirtualOrder {
  AuftragId: string;
  AuftragsNr: string;
  Status: number;
  Beschreibung: string;
  Typ: string;
}

export interface Event {
  AuftragId: string;
  Id?: string; // Keeping this as optional for backward compatibility if needed
  Titel: string;
  Datum: string;
  Beschreibung: string;
  Kategorie?: string;
}