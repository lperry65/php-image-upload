<?php

	// 
	//  ImageUpload.php
	//  
	//  Created by Lee Perry on 2010-04-18.
	// 
	
	class ImageUpload {
		
		//Class Constants
		const COPY = 1;
		const RESIZE = 2;
		const SCALE = 3;
		const MAX_FILE_SIZE = 5 * 1024 * 1024;
		
		// Member properties
		private $_destFile;
		private $_error;
		private $_fileExtension;
		private $_filename;
		private $_fileType;
		private $_tmpName;
		private $_types;

		private $_imageProcessingTypes;
		private $_maxWidth;
		private $_maxHeight;
		private $_targetImage;
		private $_imageSource;
		private $_sourceWidth;
		private $_sourceHeight;
		private $_destWidth;
		private $_destHeight;
		private $_scale;
		private $_imgLoaders;
		private $_imgCreators;
		
		function __construct(private readonly string $fileSavePath) {
		  
		  if (empty($fileSavePath)) {
		    throw new Exception('Path to save file is required');
		  }
																					
			// Supported Types.
			$this->_types = array('image/jpeg', 'image/png', 'image/gif');
			
			// Image loaders.
			$this->_imgLoaders = array(
				'image/jpeg' => 'imagecreatefromjpeg',
				'image/png' => 'imagecreatefrompng',
				'image/gif' => 'imagecreatefromgif'
			);
			
			// Image creators
			$this->_imgCreators = array(
				'image/jpeg' => 'imagejpeg',
				'image/png' => 'imagepng',
				'image/gif' => 'imagegif'				
			);
			
			// Processing types
			$this->_imageProcessingTypes = array(self::COPY, self::RESIZE, self::SCALE);
		}

		/**
		 * public function uploadImage
		 *
		 * @param string $image 
		 * @param string $filename 
		 * @param int $imageProcessingType 
		 * @param int $maxWidth 
		 * @param int $maxHeight 
		 * @return void
		 * @author Lee Perry
		 */
		
		public function uploadImage(string $image, string $filename, int $imageProcessingType, int $maxWidth = 0, int $maxHeight = 0): void {
				
		  if (!is_uploaded_file($image)) {
		    throw new Exception('Invalid uploaded file.');
		  }

		  if (filesize($image) > self::MAX_FILE_SIZE) {
		    throw new Exception('File exceeds maximum allowed size.');
		  }

		  $filename = ltrim(preg_replace('/[^a-zA-Z0-9_\-]/', '', basename($filename)), '-');

		  if (!empty($filename)) {
		   $this->_filename = $filename;
		  } else {
				throw new Exception('File name is required');
		  }
			
			// Get image details.
			$imageInfo = getimagesize($image);
						
			list($this->_fileType, $this->_fileExtension) = explode('/', $imageInfo['mime']);
							
			// Is this a supported file type.
			if (in_array($imageInfo['mime'], $this->_types)) {
				
				// Get source dimensions.
				$this->_sourceWidth = $imageInfo[0];
				$this->_sourceHeight = $imageInfo[1];
				
				// Select relavent image loader.
				$fileLoader = $this->_imgLoaders[$imageInfo['mime']];
				
				// Load source
				$this->_imageSource = $fileLoader($image);

				// What type of processing is required
				if (in_array($imageProcessingType, $this->_imageProcessingTypes)) {
           
           // Check desired image size
				  if ($imageProcessingType != self::COPY ) {
             if ($maxWidth > 0) { 
               $this->_maxWidth = $maxWidth;
             } else {
               throw new Exception('Invalid width for processing type');
             }

             if ($maxHeight > 0) { 
               $this->_maxHeight = $maxHeight;
             } else {
               throw new Exception('Invalid height for processing type');
             }
				  }
           
           // Process image 
					match ($imageProcessingType) {
						self::COPY => $this->_targetImage = $this->_imageSource,
						self::RESIZE => (function (): void {
							$this->_destWidth = $this->_maxWidth;
							$this->_destHeight = $this->_maxHeight;
							$this->createImage();
						})(),
						self::SCALE => (function (): void {
							$this->scaleImage();
							$this->createImage();
						})(),
					};
				} else {
				 throw new Exception('Invalid image process type');
				}
				
				// Destination file.
				$this->_destFile = $this->fileSavePath.'/'.$this->_filename.'.'.$this->_fileExtension;

				// Save image using the correct format.
				$imgCreator = $this->_imgCreators[$imageInfo['mime']] ?? null;
				if ($imgCreator === null) {
					throw new Exception('Unsupported file type.');
				}
				$imgCreator($this->_targetImage, $this->_destFile);
				
				// Free up memory (guard against double-free in COPY mode)
				if ($this->_targetImage !== $this->_imageSource) {
					imagedestroy($this->_imageSource);
				}
				imagedestroy($this->_targetImage);
				
			} else {
				throw new Exception('Unsupported file type.');
			}					
		}
		
		/**
		 * private function scaleImage
		 *
		 * @return void
		 * @author Lee Perry
		 */
		
		private function scaleImage(): void {
			if ($this->_sourceWidth > $this->_sourceHeight) {
				$this->_destWidth = $this->_maxWidth;
				$this->_destHeight = floor ($this->_sourceHeight * ($this->_maxWidth/$this->_sourceWidth));
				
				// check height
				if ($this->_destHeight > $this->_maxHeight) {
					$this->_destWidth = floor ($this->_maxWidth * ($this->_maxHeight/$this->_destHeight));
					$this->_destHeight = $this->_maxHeight;
				}
			} else if ($this->_sourceHeight > $this->_sourceWidth){
				$this->_destHeight = $this->_maxHeight;
				$this->_destWidth = floor ($this->_sourceWidth * ($this->_maxHeight/$this->_sourceHeight));

				// Check width
				if ($this->_destWidth > $this->_maxWidth) {
					$this->_destHeight = floor ($this->_maxHeight * ($this->_maxWidth/$this->_destWidth));
					$this->_destWidth = $this->_maxWidth;
				}
			} else {
				$this->_destWidth = $this->_maxWidth;
				$this->_destHeight = floor ($this->_sourceHeight * ($this->_maxWidth/$this->_sourceWidth));
				
				// check height
				if ($this->_destHeight > $this->_maxHeight) {
					$this->_destWidth = floor ($this->_maxWidth * ($this->_maxHeight/$this->_destHeight));
					$this->_destHeight = $this->_maxHeight;
				}
			}														
		}
		
		/**
		 * private function createImage
		 *
		 * @return void
		 * @author Lee Perry
		 */
		
		private function createImage(): void {
			// Create blank destination image.
			$this->_targetImage = imagecreatetruecolor($this->_destWidth, $this->_destHeight);

			// Preserve transparency for PNG and GIF images.
			if (in_array($this->_fileExtension, ['png', 'gif'])) {
				imagealphablending($this->_targetImage, false);
				imagesavealpha($this->_targetImage, true);
				$transparent = imagecolorallocatealpha($this->_targetImage, 0, 0, 0, 127);
				imagefill($this->_targetImage, 0, 0, $transparent);
			}

			// Resize the original picture and copy it onto the newly created image object.
			imagecopyresampled(
				$this->_targetImage,
				$this->_imageSource, 
				0, 0, 0, 0, 
				$this->_destWidth, 
				$this->_destHeight, 
				$this->_sourceWidth, 
				$this->_sourceHeight
			);
		}
	}
