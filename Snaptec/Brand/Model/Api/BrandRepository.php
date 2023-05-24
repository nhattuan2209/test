<?php
namespace Snaptec\Brand\Model\Api;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Snaptec\Brand\Api\BrandInterface;
use Snaptec\Brand\Model\BrandFactory;
use Snaptec\Brand\Model\ResourceModel\Brand as BrandResource;

class BrandRepository implements BrandInterface
{
    private $brandFactory;
    protected $brandResource;
    protected $uploaderFactory;
    protected $request;
    protected $filesystem;
    private $file;
    private $directoryList;

    public function __construct(
        BrandFactory $brandFactory,
        BrandResource $brandResource,
        UploaderFactory $uploaderFactory,
        Http $request,
        Filesystem $filesystem,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->brandFactory = $brandFactory;
        $this->brandResource = $brandResource;
        $this->uploaderFactory = $uploaderFactory;
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    public function saveBrand($id, $title, $content)
    {
        if (empty($id)) {
            // Check title or content is empty
            if ($title == null) {
                throw new LocalizedException(__('Please enter the title before saving.'));
            }

            if ($content == null) {
                throw new LocalizedException(__('Please enter the content before saving.'));
            }

            // Create a new instance of Brand model
            $brand = $this->brandFactory->create();

            // Set title and content in the model
            $brand->setTitle($title);
            $brand->setContent($content);
        } else {
            // Check if ID is a number
            if (!is_numeric($id)) {
                throw new LocalizedException(__('Invalid ID. Please enter a numeric value.'));
            }

            // Check if the Brand exists in the database
            $brand = $this->brandFactory->create()->load($id);
            if (!$brand->getId()) {
                throw new LocalizedException(__('Brand with the specified ID does not exist.'));
            }

            // Update Brand information
            $brand->setTitle($title);
            $brand->setContent($content);
        }

        // Handle image upload
        $uploader = $this->uploaderFactory->create(['fileId' => 'image']);
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
        if (!$uploader->validateFile()) {
            throw new LocalizedException(__('Invalid image format.'));
        }
        $uploader->setAllowRenameFiles(false);
        $uploader->setFilesDispersion(false);
        $imageDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $result = $uploader->save($imageDirectory->getAbsolutePath('brand/images'));

        if (!$result) {
            throw new LocalizedException(__('Failed to save image.'));
        }
        $fileName = $uploader->getUploadedFileName();
        $imagePath = 'brand/images/' . $fileName;
        $result['file'] = $fileName;


            // Xóa ảnh cũ (nếu có)
            if ($brand->getId()) {
                $oldImagePath = $brand->getImage();
                if ($oldImagePath) {
                    $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                    $oldImagePath = 'brand/images/' . $oldImagePath;
                    $mediaDirectory->delete($oldImagePath);
                }
            }



        $brand->setImage($result['file']);
        $this->brandResource->save($brand);

        // Return the data
        $brands = [];
        $brands[] = [
            'id' => $brand->getId(),
            'title' => $brand->getTitle(),
            'content' => $brand->getContent(),
            'image' => $brand->getImage(),
        ];
        return $brands;
    }
}
