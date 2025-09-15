-- Add categories table and modify notes table to include category_id
-- Run this script to add category functionality

-- Create categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, name)
);

-- Add category_id column to notes table
ALTER TABLE notes ADD COLUMN category_id INT NULL AFTER user_id;
ALTER TABLE notes ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

-- Create index for better performance
CREATE INDEX idx_notes_category_id ON notes(category_id);
CREATE INDEX idx_categories_user_id ON categories(user_id);

-- Insert default categories for existing users
INSERT INTO categories (user_id, name, color, description)
SELECT DISTINCT user_id, 'General', '#3498db', 'Default category for notes'
FROM notes
WHERE user_id IS NOT NULL;

-- Update existing notes to use the default category
UPDATE notes n
SET category_id = (
    SELECT c.id 
    FROM categories c 
    WHERE c.user_id = n.user_id 
    AND c.name = 'General' 
    LIMIT 1
)
WHERE n.category_id IS NULL AND n.user_id IS NOT NULL;