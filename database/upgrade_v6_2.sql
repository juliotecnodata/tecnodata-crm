-- Tecnodata CRM V6.2
UPDATE financial_movements
SET status='PAGTO_PARCIAL'
WHERE UPPER(status)='PAGTOPARCIAL';
