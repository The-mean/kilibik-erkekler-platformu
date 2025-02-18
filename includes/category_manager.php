<?php
class CategoryManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllCategories() {
        return $this->db->query(
            "SELECT c.*, COUNT(t.id) as topic_count 
            FROM categories c
            LEFT JOIN topics t ON c.id = t.category_id AND t.is_deleted = 0
            GROUP BY c.id
            ORDER BY c.name ASC"
        )->fetchAll();
    }
    
    public function getCategory($id) {
        return $this->db->fetchOne(
            "SELECT * FROM categories WHERE id = :id",
            [':id' => $id]
        );
    }
    
    public function getCategoryBySlug($slug) {
        return $this->db->fetchOne(
            "SELECT * FROM categories WHERE slug = :slug",
            [':slug' => $slug]
        );
    }
    
    public function getAllTags() {
        return $this->db->query(
            "SELECT * FROM tags ORDER BY usage_count DESC, name ASC"
        )->fetchAll();
    }
    
    public function getPopularTags($limit = 10) {
        return $this->db->query(
            "SELECT t.*, COUNT(tt.topic_id) as usage_count 
            FROM tags t
            LEFT JOIN topic_tags tt ON t.id = tt.tag_id
            GROUP BY t.id
            ORDER BY usage_count DESC, t.name ASC
            LIMIT :limit",
            [':limit' => $limit]
        )->fetchAll();
    }
    
    public function getTagsByTopic($topicId) {
        return $this->db->query(
            "SELECT t.* FROM tags t 
            INNER JOIN topic_tags tt ON t.id = tt.tag_id 
            WHERE tt.topic_id = :topic_id 
            ORDER BY t.name ASC",
            [':topic_id' => $topicId]
        )->fetchAll();
    }
    
    public function addTag($name) {
        $slug = $this->createSlug($name);
        
        // Etiket zaten var mı kontrol et
        $existingTag = $this->db->fetchOne(
            "SELECT id FROM tags WHERE slug = :slug",
            [':slug' => $slug]
        );
        
        if ($existingTag) {
            return $existingTag['id'];
        }
        
        // Yeni etiket ekle
        return $this->db->insert('tags', [
            'name' => $name,
            'slug' => $slug,
            'usage_count' => 0
        ]);
    }
    
    public function addTagsToTopic($topicId, $tags) {
        // Önce mevcut etiketleri temizle
        $this->db->query(
            "DELETE FROM topic_tags WHERE topic_id = :topic_id",
            [':topic_id' => $topicId]
        );
        
        // Yeni etiketleri ekle
        foreach ($tags as $tag) {
            $tagId = $this->addTag($tag);
            
            $this->db->query(
                "INSERT INTO topic_tags (topic_id, tag_id) VALUES (:topic_id, :tag_id)",
                [
                    ':topic_id' => $topicId,
                    ':tag_id' => $tagId
                ]
            );
            
            // Kullanım sayısını güncelle
            $this->db->query(
                "UPDATE tags SET usage_count = (
                    SELECT COUNT(*) FROM topic_tags WHERE tag_id = :tag_id
                ) WHERE id = :tag_id",
                [':tag_id' => $tagId]
            );
        }
    }
    
    public function getTopicsByCategory($categoryId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        return $this->db->query(
            "SELECT t.*, u.username, u.avatar,
            c.name as category_name, c.slug as category_slug,
            COUNT(DISTINCT cm.id) as comment_count,
            COUNT(DISTINCT l.id) as like_count,
            CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as is_liked,
            CASE WHEN ub.id IS NOT NULL THEN 1 ELSE 0 END as is_bookmarked
            FROM topics t 
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN comments cm ON t.id = cm.topic_id
            LEFT JOIN likes l ON t.id = l.topic_id
            LEFT JOIN likes ul ON t.id = ul.topic_id AND ul.user_id = :user_id
            LEFT JOIN bookmarks ub ON t.id = ub.topic_id AND ub.user_id = :user_id
            WHERE t.category_id = :category_id AND t.is_deleted = 0
            GROUP BY t.id
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset",
            [
                ':category_id' => $categoryId,
                ':user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0,
                ':limit' => $perPage,
                ':offset' => $offset
            ]
        )->fetchAll();
    }
    
    public function getTopicsByTag($tagSlug, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        return $this->db->query(
            "SELECT t.*, u.username, u.avatar,
            c.name as category_name, c.slug as category_slug,
            COUNT(DISTINCT cm.id) as comment_count,
            COUNT(DISTINCT l.id) as like_count,
            CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as is_liked,
            CASE WHEN ub.id IS NOT NULL THEN 1 ELSE 0 END as is_bookmarked
            FROM topics t 
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN comments cm ON t.id = cm.topic_id
            LEFT JOIN likes l ON t.id = l.topic_id
            LEFT JOIN likes ul ON t.id = ul.topic_id AND ul.user_id = :user_id
            LEFT JOIN bookmarks ub ON t.id = ub.topic_id AND ub.user_id = :user_id
            INNER JOIN topic_tags tt ON t.id = tt.topic_id
            INNER JOIN tags tag ON tt.tag_id = tag.id
            WHERE tag.slug = :tag_slug AND t.is_deleted = 0
            GROUP BY t.id
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset",
            [
                ':tag_slug' => $tagSlug,
                ':user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0,
                ':limit' => $perPage,
                ':offset' => $offset
            ]
        )->fetchAll();
    }
    
    public function getCategoryTopicCount($categoryId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count 
            FROM topics 
            WHERE category_id = :category_id AND is_deleted = 0",
            [':category_id' => $categoryId]
        );
        return $result['count'];
    }
    
    public function getTagTopicCount($tagSlug) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT t.id) as count 
            FROM topics t
            INNER JOIN topic_tags tt ON t.id = tt.topic_id
            INNER JOIN tags tag ON tt.tag_id = tag.id
            WHERE tag.slug = :tag_slug AND t.is_deleted = 0",
            [':tag_slug' => $tagSlug]
        );
        return $result['count'];
    }
    
    private function createSlug($str) {
        $str = mb_strtolower($str, 'UTF-8');
        $str = str_replace(
            ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', ' '],
            ['i', 'g', 'u', 's', 'o', 'c', '-'],
            $str
        );
        $str = preg_replace('/[^a-z0-9\-]/', '', $str);
        $str = preg_replace('/-+/', '-', $str);
        return trim($str, '-');
    }
} 