-- KUTT SUKA MAKMUR - Seed: pengaturan background kartu anggota (idempoten)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('card_bg_type',     'color'),
  ('card_bg_color',    '#0b7a3e'),
  ('card_bg_image',    ''),
  ('card_bg_opacity',  '0.25');
