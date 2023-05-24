<?php
namespace Snaptec\Brand\Model\Resolver;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\GraphQlInputException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Snaptec\Brand\Model\BrandFactory;

class BrandSave implements ResolverInterface
{
    protected $brandFactory;
    protected $uploaderFactory;
    protected $filesystem;
    protected $file;
    protected $mediaDirectory;

    public function __construct(
        BrandFactory $brandFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->brandFactory = $brandFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->mediaDirectory = $filesystem->getDirectoryRead(
            $directoryList::MEDIA
        );
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $result = [];
        try {
            // Get title and content from args with default values of null
            $title = $args["title"];
            $content = $args["content"];
            $id = $args["id"];

            // Check if title is null
            if (empty($title)) {
                throw new \Exception (__("Enter title before save."));
            }

            // Check if content is null
            if (empty($content)) {
                throw new \Exception (__("Enter content before save."));
            }

            $brand = $this->brandFactory->create();

            if (!empty($id)) {
                $brand->load($id);
                if (!is_numeric($id)) {
                    throw new \Exception (__("Please enter a numeric ID."));
                }
                $existingBrand = $brand->load($id);
                if (!$existingBrand->getId($id)) {
                    throw new \Exception (__("Brand does not exist."));
                }
            }

            $brand->setTitle($title);
            $brand->setContent($content);
            $brand->save();

            // Upload image
            if (isset($args["image"])) {
                $image = $args["image"];
                $encoded_string = $image["base64_image"];

                // Process and save the image to the media directory

                $mediaPath = $this->mediaDirectory->getAbsolutePath(
                    "brand/images/"
                );

                $imageContent = base64_decode($encoded_string);

                $f = finfo_open();

                $mime_type = finfo_buffer(
                    $f,
                    $imageContent,
                    FILEINFO_MIME_TYPE
                );

                $imageName =
                time() . "." . str_replace("image/", "", $mime_type);
                $filePath = $mediaPath . $imageName;
                $this->file->filePutContents($filePath, $imageContent);
                $result["image_url"] = "brand/images/" . $imageName;
            
                // Lấy ảnh cũ của brand nếu có
                $oldImage = $brand->getImage();
                
                // Update brand image
                $brand->setImage($result["image_url"]);
                $brand->save();

                if (!empty($oldImage)) {
                    $oldImagePath = $this->mediaDirectory->getAbsolutePath($oldImage);
                    $this->file->deleteFile($oldImagePath);
                }
                

            }
        } catch (\Exception $e) {
            throw $e;
            // } catch (\Exception $e) {
            //     throw $e;
        } catch (\Exception $e) {
            if (
                !$e instanceof CouldNotSaveException &&
                !$e instanceof GraphQlInputException
            ) {
                throw new CouldNotSaveException(__("Could not save brand"), $e);
            }
            throw $e;
        }
        return [
            "id" => $brand->getId(),
            "title" => $brand->getTitle(),
            "content" => $brand->getContent(),
            "image_url" => $result["image_url"],
        ];
    }
}
