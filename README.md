# Install SQL Anywhere Client
> https://help.sap.com/docs/SUPPORT_CONTENT/sqlany/3362971128.html

# Install Backend
> composer_install.bat
> .\App.bat start

# Install frontend
> cd frontend
> npm install
> npm run dev (or 'npm run build' when production ready)




# Database Setup (Trigger-Based Strategy)
For the trigger-based strategy to work, you need to set up the changes table and triggers in your Sybase SQL Anywhere database (and MySQL if applicable). Here’s the SQL to create them:

## Sybase SQL Anywhere
CREATE TABLE changes (
    id INTEGER NOT NULL DEFAULT AUTOINCREMENT,
    table_name VARCHAR(100),
    operation VARCHAR(10),
    record_id VARCHAR(50),
    change_data LONG VARCHAR,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TRIGGER after_order_insert
AFTER INSERT ON orders
REFERENCING NEW AS new_row
FOR EACH ROW
BEGIN
    INSERT INTO changes (table_name, operation, record_id, change_data)
    VALUES (
        'orders',
        'INSERT',
        new_row.AuftragId,
        JSON_OBJECT(
            'AuftragId', new_row.AuftragId,
            'AuftragsNr', new_row.AuftragsNr,
            'AuftragsKennung', new_row.AuftragsKennung,
            'Datum_Erfassung', CAST(new_row.Datum_Erfassung AS VARCHAR),
            'BestellNr', new_row.BestellNr,
            'Liefertermin', CAST(new_row.Liefertermin AS VARCHAR),
            'KundenNr', new_row.KundenNr,
            'KundenMatchcode', new_row.KundenMatchcode,
            'Status', new_row.Status
        )
    );
END;

CREATE TRIGGER after_order_update
AFTER UPDATE ON orders
REFERENCING NEW AS new_row
FOR EACH ROW
BEGIN
    INSERT INTO changes (table_name, operation, record_id, change_data)
    VALUES (
        'orders',
        'UPDATE',
        new_row.AuftragId,
        JSON_OBJECT(
            'AuftragId', new_row.AuftragId,
            'AuftragsNr', new_row.AuftragsNr,
            'AuftragsKennung', new_row.AuftragsKennung,
            'Datum_Erfassung', CAST(new_row.Datum_Erfassung AS VARCHAR),
            'BestellNr', new_row.BestellNr,
            'Liefertermin', CAST(new_row.Liefertermin AS VARCHAR),
            'KundenNr', new_row.KundenNr,
            'KundenMatchcode', new_row.KundenMatchcode,
            'Status', new_row.Status
        )
    );
END;

CREATE TRIGGER after_order_delete
AFTER DELETE ON orders
REFERENCING OLD AS old_row
FOR EACH ROW
BEGIN
    INSERT INTO changes (table_name, operation, record_id, change_data)
    VALUES (
        'orders',
        'DELETE',
        old_row.AuftragId,
        JSON_OBJECT(
            'AuftragId', old_row.AuftragId
        )
    );
END;

Notes:

Sybase SQL Anywhere supports JSON_OBJECT in newer versions. If your version doesn’t support it, you can concatenate the JSON string manually (e.g., '{"AuftragId":"' || new_row.AuftragId || '", ...}').
Adjust the table and column names (orders, AuftragId, etc.) to match your actual schema.


## MySQL (Optional)
CREATE TABLE changes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100),
    operation VARCHAR(10),
    record_id VARCHAR(50),
    change_data JSON,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

DELIMITER //

CREATE TRIGGER after_order_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    INSERT INTO changes (table_name, operation, record_id, change_data)
    VALUES (
        'orders',
        'INSERT',
        NEW.AuftragId,
        JSON_OBJECT(
            'AuftragId', NEW.AuftragId,
            'AuftragsNr', NEW.AuftragsNr,
            'AuftragsKennung', NEW.AuftragsKennung,
            'Datum_Erfassung', NEW.Datum_Erfassung,
            'BestellNr', NEW.BestellNr,
            'Liefertermin', NEW.Liefertermin,
            'KundenNr', NEW.KundenNr,
            'KundenMatchcode', NEW.KundenMatchcode,
            'Status', NEW.Status
        )
    );
END//

CREATE TRIGGER after_order_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    INSERT INTO changes (table_name, operation, record_id, change_data)
    VALUES (
        'orders',
        'UPDATE',
        NEW.AuftragId,
        JSON_OBJECT(
            'AuftragId', NEW.AuftragId,
            'AuftragsNr', NEW.AuftragsNr,
            'AuftragsKennung', NEW.AuftragsKennung,
            'Datum_Erfassung', NEW.Datum_Erfassung,
            'BestellNr', NEW.BestellNr,
            'Liefertermin', NEW.Liefertermin,
            'KundenNr', NEW.KundenNr,
            'KundenMatchcode', NEW.KundenMatchcode,
            'Status', NEW.Status
        )
    );
END//

CREATE TRIGGER after_order_delete
AFTER DELETE ON orders
FOR EACH ROW
BEGIN
    INSERT INTO changes (table_name, operation, record_id, change_data)
    VALUES (
        'orders',
        'DELETE',
        OLD.AuftragId,
        JSON_OBJECT(
            'AuftragId', OLD.AuftragId
        )
    );
END//

DELIMITER ;