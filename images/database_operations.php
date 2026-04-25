
<?php
function deleteImage($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM images WHERE id = ? AND sessao = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao excluir imagem: " . $e->getMessage());
        return false;
    }
}

function editImage($pdo, $id, $titulo, $intencao) {
    try {
        $stmt = $pdo->prepare("UPDATE images SET titulo = ?, intencao = ? WHERE id = ? AND sessao = ?");
        $stmt->execute([$titulo, $intencao, $id, $_SESSION['user_id']]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao editar imagem: " . $e->getMessage());
        return false;
    }
}

function addImage($pdo, $titulo, $image_base64, $sessao, $intencao = 0) {
    try {
        $stmt = $pdo->prepare("INSERT INTO images (titulo, image, sessao, intencao) VALUES (?, ?, ?, ?)");
        $stmt->execute([$titulo, $image_base64, $sessao, $intencao]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao inserir imagem: " . $e->getMessage());
        return false;
    }
}

function getImages($pdo, $intencao = null) {
    try {
        $params = [$_SESSION['user_id']];
        $sql = "SELECT * FROM images WHERE sessao = ?";
        
        if ($intencao !== null && $intencao !== '0') {
            $sql .= " AND intencao = ?";
            $params[] = $intencao;
        } elseif ($intencao === '0') {
            $sql .= " AND (intencao IS NULL OR intencao = 0)";
        }
        
        $sql .= " ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro na query de imagens: " . $e->getMessage());
        return [];
    }
}

function getIntencoes($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM intencao WHERE sessao = ? OR sessao IS NULL ORDER BY titulo");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar intenções: " . $e->getMessage());
        return [];
    }
}

function processImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Erro no upload: " . $file['error']);
        return null;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        error_log("Tipo de arquivo não permitido: " . $file['type']);
        return null;
    }

    try {
        // Carregar imagem
        $image = imagecreatefromstring(file_get_contents($file['tmp_name']));
        if (!$image) {
            error_log("Falha ao criar imagem a partir do arquivo");
            return null;
        }

        // Redimensionar mantendo proporção se necessário
        $max_width = 1024;
        $max_height = 1024;
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $max_width || $height > $max_height) {
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = round($width * $ratio);
            $new_height = round($height * $ratio);

            $new_image = imagecreatetruecolor($new_width, $new_height);
            if (!$new_image) {
                error_log("Falha ao criar nova imagem para redimensionamento");
                return null;
            }

            if (!imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height)) {
                error_log("Falha ao redimensionar imagem");
                return null;
            }

            imagedestroy($image);
            $image = $new_image;
        }

        // Converter para JPEG e otimizar
        ob_start();
        if (!imagejpeg($image, null, 85)) {
            error_log("Falha ao converter imagem para JPEG");
            return null;
        }
        $image_data = ob_get_clean();
        imagedestroy($image);

        return base64_encode($image_data);
    } catch (Exception $e) {
        error_log("Erro ao processar imagem: " . $e->getMessage());
        return null;
    }
}
?>
