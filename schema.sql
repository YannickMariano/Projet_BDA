-- ============================================
-- SCHEMA v2 : Gestion Bancaire avec Rôles
-- ============================================

DROP TABLE IF EXISTS audit_compte CASCADE;
DROP TABLE IF EXISTS compte CASCADE;
DROP TABLE IF EXISTS utilisateurs CASCADE;

-- ============================================
-- TABLE : Utilisateurs (login / rôles)
-- ============================================
CREATE TABLE utilisateurs (
    id          SERIAL PRIMARY KEY,
    username    VARCHAR(50)  UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,   -- hash bcrypt côté PHP
    role        VARCHAR(10)  NOT NULL CHECK (role IN ('admin', 'user')),
    nom_complet VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE : Comptes bancaires
-- ============================================
CREATE TABLE compte (
    num_compte  SERIAL PRIMARY KEY,
    nom_client  VARCHAR(100) NOT NULL,
    solde       NUMERIC(15, 2) DEFAULT 0.00,
    user_id     INTEGER NOT NULL REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE : Audit
-- ============================================
CREATE TABLE audit_compte (
    id           SERIAL PRIMARY KEY,
    type_action  VARCHAR(20) NOT NULL CHECK (type_action IN ('INSERTION','MODIFICATION','SUPPRESSION')),
    date_maj     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    num_compte   INTEGER,
    nom_client   VARCHAR(100),
    solde_ancien NUMERIC(15, 2),
    solde_nouv   NUMERIC(15, 2),
    utilisateur  VARCHAR(50)   -- username de l'app
);

-- ============================================
-- TRIGGERS
-- ============================================

-- AFTER INSERT
CREATE OR REPLACE FUNCTION fn_audit_insert()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO audit_compte (type_action, num_compte, nom_client, solde_ancien, solde_nouv, utilisateur)
    VALUES ('INSERTION', NEW.num_compte, NEW.nom_client, NULL, NEW.solde,
            (SELECT username FROM utilisateurs WHERE id = NEW.user_id));
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trig_after_insert
AFTER INSERT ON compte
FOR EACH ROW EXECUTE FUNCTION fn_audit_insert();

-- AFTER UPDATE
CREATE OR REPLACE FUNCTION fn_audit_update()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO audit_compte (type_action, num_compte, nom_client, solde_ancien, solde_nouv, utilisateur)
    VALUES ('MODIFICATION', NEW.num_compte, NEW.nom_client, OLD.solde, NEW.solde,
            (SELECT username FROM utilisateurs WHERE id = NEW.user_id));
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trig_after_update
AFTER UPDATE ON compte
FOR EACH ROW EXECUTE FUNCTION fn_audit_update();

-- AFTER DELETE
CREATE OR REPLACE FUNCTION fn_audit_delete()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO audit_compte (type_action, num_compte, nom_client, solde_ancien, solde_nouv, utilisateur)
    VALUES ('SUPPRESSION', OLD.num_compte, OLD.nom_client, OLD.solde, NULL,
            (SELECT username FROM utilisateurs WHERE id = OLD.user_id));
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trig_after_delete
AFTER DELETE ON compte
FOR EACH ROW EXECUTE FUNCTION fn_audit_delete();

-- ============================================
-- DONNÉES DE TEST
-- Mots de passe : admin123 / user123
-- (générés avec password_hash en PHP)
-- ============================================

-- Admin
INSERT INTO utilisateurs (username, password, role, nom_complet)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrateur');

-- Utilisateurs
INSERT INTO utilisateurs (username, password, role, nom_complet)
VALUES ('alice',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Alice Dupont');
INSERT INTO utilisateurs (username, password, role, nom_complet)
VALUES ('bob',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Bob Martin');
INSERT INTO utilisateurs (username, password, role, nom_complet)
VALUES ('clara',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Clara Leroy');

-- Comptes bancaires (liés aux utilisateurs)
INSERT INTO compte (nom_client, solde, user_id)
VALUES ('Alice Dupont', 15000.00, (SELECT id FROM utilisateurs WHERE username='alice'));

INSERT INTO compte (nom_client, solde, user_id)
VALUES ('Bob Martin', 8500.50, (SELECT id FROM utilisateurs WHERE username='bob'));

INSERT INTO compte (nom_client, solde, user_id)
VALUES ('Bob Martin - Épargne', 22000.00, (SELECT id FROM utilisateurs WHERE username='bob'));

INSERT INTO compte (nom_client, solde, user_id)
VALUES ('Clara Leroy', 500.00, (SELECT id FROM utilisateurs WHERE username='clara'));

-- Quelques mouvements pour peupler l'audit
UPDATE compte SET solde = 16500.00 WHERE nom_client = 'Alice Dupont';
UPDATE compte SET solde = 9200.00  WHERE nom_client = 'Bob Martin';
