<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private string $avatarsDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $avatarsDirectory, SluggerInterface $slugger)
    {
        $this->avatarsDirectory = $avatarsDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getAvatarsDirectory(), $fileName);

            // Изменяем размер изображения
            $this->resizeImage($this->getAvatarsDirectory().'/'.$fileName);
        } catch (FileException $e) {
            throw new FileException('Ошибка при загрузке файла');
        }

        return $fileName;
    }

    private function resizeImage(string $filePath): void
    {
        // Проверяем существование файла
        if (!file_exists($filePath)) {
            return;
        }

        // Получаем размеры изображения
        list($width, $height) = getimagesize($filePath);

        $maxSize = 400;

        // Если изображение меньше 400x400, не изменяем
        if ($width <= $maxSize && $height <= $maxSize) {
            return;
        }

        // Вычисляем новые размеры с сохранением пропорций
        if ($width > $height) {
            $newWidth = $maxSize;
            $newHeight = intval($height * ($maxSize / $width));
        } else {
            $newHeight = $maxSize;
            $newWidth = intval($width * ($maxSize / $height));
        }

        // Создаем новое изображение
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $source = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $source = imagecreatefrompng($filePath);
                break;
            case 'gif':
                $source = imagecreatefromgif($filePath);
                break;
            case 'webp':
                $source = imagecreatefromwebp($filePath);
                break;
            default:
                return;
        }

        // Создаем пустое изображение нужного размера
        $destination = imagecreatetruecolor($newWidth, $newHeight);

        // Сохраняем прозрачность для PNG и GIF
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
        }

        // Копируем с изменением размера
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Сохраняем
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($destination, $filePath, 90);
                break;
            case 'png':
                imagepng($destination, $filePath, 9);
                break;
            case 'gif':
                imagegif($destination, $filePath);
                break;
            case 'webp':
                imagewebp($destination, $filePath, 90);
                break;
        }

        // Освобождаем память
        imagedestroy($source);
        imagedestroy($destination);
    }

    public function getAvatarsDirectory(): string
    {
        return $this->avatarsDirectory;
    }

    public function uploadAttachment(UploadedFile $file, string $subdirectory = 'attachments'): array
    {
        // Получаем данные ДО перемещения файла
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        $safeFilename = $this->slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME));
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $targetDirectory = $this->avatarsDirectory . '/../' . $subdirectory;

        // Создаём директорию если не существует
        if (!is_dir($targetDirectory)) {
            if (!mkdir($targetDirectory, 0777, true)) {
                throw new \RuntimeException('Не удалось создать директорию: ' . $targetDirectory);
            }
        }

        // Проверяем права на запись
        if (!is_writable($targetDirectory)) {
            throw new \RuntimeException('Нет прав на запись в директорию: ' . $targetDirectory);
        }

        try {
            $file->move($targetDirectory, $fileName);
        } catch (FileException $e) {
            throw new FileException('Ошибка при загрузке файла: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('Неожиданная ошибка при загрузке файла: ' . $e->getMessage());
        }

        // Возвращаем данные, которые получили ДО перемещения
        return [
            'filename' => $fileName,
            'originalFilename' => $originalFilename,
            'mimeType' => $mimeType,
            'fileSize' => $fileSize,
        ];
    }

    public function deleteAttachment(string $filename, string $subdirectory = 'attachments'): void
    {
        $filePath = $this->avatarsDirectory . '/../' . $subdirectory . '/' . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

}