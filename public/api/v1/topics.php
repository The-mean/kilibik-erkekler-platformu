<?php
require_once '../../../includes/category_manager.php';

$categoryManager = new CategoryManager();

switch ($method) {
    case 'GET':
        // Query parametrelerini al
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
        $category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $tag = isset($_GET['tag']) ? trim($_GET['tag']) : null;

        // Toplam sayfa sayısını hesapla
        if ($category_id) {
            $total = $categoryManager->getCategoryTopicCount($category_id);
            $topics = $categoryManager->getTopicsByCategory($category_id, $page, $per_page);
        } elseif ($tag) {
            $total = $categoryManager->getTagTopicCount($tag);
            $topics = $categoryManager->getTopicsByTag($tag, $page, $per_page);
        } else {
            $topics_query = "SELECT 
                t.*, 
                u.username, 
                u.avatar,
                c.name as category_name,
                c.slug as category_slug,
                COUNT(DISTINCT cm.id) as comment_count,
                COUNT(DISTINCT l.id) as like_count
            FROM topics t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN comments cm ON t.id = cm.topic_id
            LEFT JOIN likes l ON t.id = l.topic_id
            WHERE t.is_deleted = 0
            GROUP BY t.id
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset";

            $total_query = "SELECT COUNT(*) as count FROM topics WHERE is_deleted = 0";
            $result = $db->fetchOne($total_query);
            $total = $result['count'];

            $topics = $db->fetchAll($topics_query, [
                ':limit' => $per_page,
                ':offset' => ($page - 1) * $per_page
            ]);
        }

        // Her konu için etiketleri al
        foreach ($topics as &$topic) {
            $topic['tags'] = $categoryManager->getTagsByTopic($topic['id']);
            
            // Hassas bilgileri temizle
            unset($topic['is_deleted']);
            unset($topic['ip_address']);
        }

        $total_pages = ceil($total / $per_page);

        echo json_encode([
            'data' => $topics,
            'meta' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => $total_pages
            ]
        ]);
        break;

    case 'POST':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['title']) || !isset($data['content']) || !isset($data['category_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        try {
            $db->beginTransaction();

            // Yeni konu ekle
            $topic_id = $db->insert('topics', [
                'title' => $data['title'],
                'content' => $data['content'],
                'category_id' => $data['category_id'],
                'user_id' => $_SESSION['user_id'],
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);

            // Etiketleri ekle
            if (isset($data['tags']) && is_array($data['tags'])) {
                $categoryManager->addTagsToTopic($topic_id, $data['tags']);
            }

            $db->commit();

            $topic = $db->fetchOne(
                "SELECT t.*, u.username, c.name as category_name 
                FROM topics t 
                LEFT JOIN users u ON t.user_id = u.id 
                LEFT JOIN categories c ON t.category_id = c.id 
                WHERE t.id = :id",
                [':id' => $topic_id]
            );

            $topic['tags'] = $categoryManager->getTagsByTopic($topic_id);

            echo json_encode([
                'message' => 'Topic created successfully',
                'data' => $topic
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create topic']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 