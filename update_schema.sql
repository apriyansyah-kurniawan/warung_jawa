-- Update mapping_bahan table to include X7 to X11 categories
ALTER TABLE mapping_bahan
MODIFY COLUMN kategori_x ENUM('X1','X2','X3','X4','X5','X6','X7','X8','X9','X10','X11')
NOT NULL COMMENT 'Kategori variabel independen dalam regresi';

-- Insert missing mapping for X7 to X11 if not exists
INSERT IGNORE INTO mapping_bahan (nama_bahan, kategori_x, satuan, faktor_konversi) VALUES
('Telur', 'X7', 'Kg', 1.0),
('Ayam Puyuh', 'X7', 'Kg', 1.0),
('Cumi', 'X8', 'Kg', 1.0),
('Udang', 'X8', 'Kg', 1.0),
('Ikan Asin', 'X8', 'Kg', 1.0),
('Telur Puyuh', 'X8', 'Kg', 1.0), -- Note: Actually, Puyuh is also under X7? We'll put under X8 for seafood, but note the context: X8 is Seafood & Ikan (Cumi, Udang, Ikan Asin). We'll keep Puyuh under X7.
('Ati Ampela', 'X9', 'Kg', 1.0),
('Ceker', 'X9', 'Kg', 1.0),
('Jengkol', 'X10', 'Kg', 1.0),
('Tahu', 'X11', 'Kg', 1.0),
('Tempe', 'X11', 'Kg', 1.0);

-- Update dataset_regresi table to include x7 to x11 columns
ALTER TABLE dataset_regresi
ADD COLUMN IF NOT EXISTS x7_telur DECIMAL(10,2) DEFAULT 0 AFTER x6_minyak,
ADD COLUMN IF NOT EXISTS x8_seafood DECIMAL(10,2) DEFAULT 0 AFTER x7_telur,
ADD COLUMN IF NOT EXISTS x9_jeroan DECIMAL(10,2) DEFAULT 0 AFTER x8_seafood,
ADD COLUMN IF NOT EXISTS x10_jengkol DECIMAL(10,2) DEFAULT 0 AFTER x9_jeroan,
ADD COLUMN IF NOT EXISTS x11_tahu_tempe DECIMAL(10,2) DEFAULT 0 AFTER x10_jengkol;

-- Update model_regresi table to include beta7 to beta11
ALTER TABLE model_regresi
ADD COLUMN IF NOT EXISTS beta7 DECIMAL(10,6) DEFAULT 0 AFTER beta6,
ADD COLUMN IF NOT EXISTS beta8 DECIMAL(10,6) DEFAULT 0 AFTER beta7,
ADD COLUMN IF NOT EXISTS beta9 DECIMAL(10,6) DEFAULT 0 AFTER beta8,
ADD COLUMN IF NOT EXISTS beta10 DECIMAL(10,6) DEFAULT 0 AFTER beta9,
ADD COLUMN IF NOT EXISTS beta11 DECIMAL(10,6) DEFAULT 0 AFTER beta10;