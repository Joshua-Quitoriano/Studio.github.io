-- Add new columns to students table for receipt verification
ALTER TABLE students
ADD COLUMN verification_receipt ENUM('pending', 'verified', 'reject') DEFAULT 'pending',
ADD COLUMN receipts VARCHAR(255) DEFAULT NULL;