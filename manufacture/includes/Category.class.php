<?php
require_once __DIR__ . '/../../config/database.php';

class Category {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Get all active categories
    public function getAllCategories() {
        $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get category by ID
    public function getCategoryById($id) {
        $sql = "SELECT * FROM categories WHERE id = :id AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get subcategories by category ID
    public function getSubcategoriesByCategoryId($category_id) {
        $sql = "SELECT * FROM subcategories WHERE category_id = :category_id AND is_active = 1 ORDER BY sort_order, name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['category_id' => $category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get subcategory by ID
    public function getSubcategoryById($id) {
        $sql = "SELECT * FROM subcategories WHERE id = :id AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Search categories
    public function searchCategories($search_term) {
        $sql = "SELECT * FROM categories WHERE (name LIKE :search1 OR description LIKE :search2) AND is_active = 1 ORDER BY sort_order, name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['search1' => '%' . $search_term . '%', 'search2' => '%' . $search_term . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Search subcategories
    public function searchSubcategories($search_term, $category_id = null) {
        $sql = "SELECT s.*, c.name as category_name 
                FROM subcategories s 
                JOIN categories c ON s.category_id = c.id 
                WHERE (s.name LIKE :search1 OR s.description LIKE :search2) AND s.is_active = 1";
        
        if ($category_id) {
            $sql .= " AND s.category_id = :category_id";
        }
        
        $sql .= " ORDER BY s.sort_order, s.name";
        
        $stmt = $this->pdo->prepare($sql);
        $params = ['search1' => '%' . $search_term . '%', 'search2' => '%' . $search_term . '%'];
        
        if ($category_id) {
            $params['category_id'] = $category_id;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get categories for dropdown
    public function getCategoriesForDropdown() {
        $sql = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get subcategories for dropdown
    public function getSubcategoriesForDropdown($category_id = null) {
        $sql = "SELECT id, name, category_id FROM subcategories WHERE is_active = 1";
        if ($category_id) {
            $sql .= " AND category_id = :category_id";
        }
        $sql .= " ORDER BY sort_order, name";
        
        $stmt = $this->pdo->prepare($sql);
        if ($category_id) {
            $stmt->execute(['category_id' => $category_id]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 