<?php
// Путь к каталогу с видеофайлами
// Это может быть результат символической ссылки на директорию готовых таймлапсов бота. У меня это путь /home/tbot/ff5m1/timelapse_finished
// (вы уже установили бота и он куда то собирает таймлапсы. Просто протяните эту директорию до /var/www/html/web-timelapse/timelapse
$videoDir = 'timelapse';

// Функция для создания превью из последнего кадра
function createThumbnail($videoPath, $thumbnailPath) {
    // Получаем длительность видео
    $command = "ffmpeg -i {$videoPath} 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//";
    $duration = exec($command);

    // Логируем результат команды
    error_log("Результат команды ffmpeg для файла {$videoPath}: {$duration}");

    // Проверяем, что длительность получена и соответствует формату HH:MM:SS
    if (!preg_match('/^(\d{2}):(\d{2}):(\d{2})/', $duration, $matches)) {
        throw new Exception("Не удалось получить длительность видео для файла: {$videoPath}");
    }

    // Преобразуем длительность в секунды
    $hours = (int)$matches[1];
    $minutes = (int)$matches[2];
    $seconds = (int)$matches[3];
    $totalSeconds = $hours * 3600 + $minutes * 60 + $seconds;

    // Вычитаем 1 секунду, чтобы не выйти за пределы видео
    $time = max(0, $totalSeconds - 5); // Убедимся, что время не отрицательное

    // Извлекаем кадр ближе к концу видео
    $command = "ffmpeg -ss {$time} -i {$videoPath} -vframes 1 -q:v 2 {$thumbnailPath}";
    exec($command);

    // Проверяем, что превью создано
    if (!file_exists($thumbnailPath)) {
        throw new Exception("Не удалось создать превью для файла: {$videoPath}");
    }
}

try {
    // Получаем список файлов в каталоге с видео
    $files = scandir($videoDir);
    $files = array_diff($files, array('.', '..'));

    // Массив для хранения информации о файлах
    $fileList = [];

    foreach ($files as $file) {
        // Полный путь к файлу
        $filePath = $videoDir . '/' . $file;

        // Проверяем, содержит ли имя файла пробелы
        if (strpos($file, ' ') !== false) {
            // Заменяем пробелы на _
            $newFileName = str_replace(' ', '_', $file);
            $newFilePath = $videoDir . '/' . $newFileName;
            rename($filePath, $newFilePath); // Переименовываем файл
            $file = $newFileName; // Обновляем имя файла для дальнейшей обработки
            $filePath = $newFilePath; // Обновляем путь к файлу
            error_log("Файл переименован: {$filePath} -> {$newFilePath}");
        }

        if (pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
            // Проверяем MIME-тип файла
            $mimeType = mime_content_type($filePath);
            if (strpos($mimeType, 'video/') !== 0) {
                error_log("Файл {$filePath} не является видео (MIME-тип: {$mimeType})");
                continue; // Пропускаем файл
            }

            // Путь для превью (в том же каталоге, что и видео)
            $thumbnail = $videoDir . '/' . pathinfo($file, PATHINFO_FILENAME) . '.jpg';

            try {
                // Если превью ещё не создано, создаём его
                if (!file_exists($thumbnail)) {
                    createThumbnail($filePath, $thumbnail);
                }

                // Добавляем информацию о файле в массив
                $fileList[] = [
                    'name' => $file,
                    'path' => 'timelapse/' . $file, // Относительный путь к файлу
                    'thumbnail' => 'timelapse/' . pathinfo($file, PATHINFO_FILENAME) . '.jpg', // Относительный путь к превью
                    'modified_time' => filemtime($filePath) // Время последнего изменения файла
                ];
            } catch (Exception $e) {
                // Логируем ошибку и продолжаем обработку
                error_log($e->getMessage());
                continue;
            }
        }
    }

    // Сортируем файлы по дате изменения (от нового к старому)
    usort($fileList, function($a, $b) {
        return $b['modified_time'] - $a['modified_time']; // Сортировка по убыванию
    });
} catch (Exception $e) {
    // Логируем ошибку
    error_log($e->getMessage());
    die("Произошла ошибка при обработке видео. Пожалуйста, проверьте логи.");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Таймлапсы</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Таймлапсы</h1>
        <ul class="file-list">
            <?php foreach ($fileList as $file): ?>
                <li>
                    <div class="thumbnail">
                        <a href="<?php echo $file['path']; ?>" target="_self"><img src="<?php echo $file['thumbnail']; ?>" alt="Превью"></a>
                    </div>
                    <span class="file-date"><?php echo date('Y-m-d H:i:s', $file['modified_time']); ?></span>
                    <a href="<?php echo $file['path']; ?>" class="download-btn" download>Скачать</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
