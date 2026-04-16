ALTER TABLE teks ADD COLUMN escale TEXT AFTER chassis;
CREATE INDEX idx_teks_escale ON teks (escale(100));
