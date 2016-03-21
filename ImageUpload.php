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
		
		// Member properties
		private $_destFile;
		private $_error;
		private $_fileExtension;
		private $_fileSavePath;
		private $_filename;
		private $_fileType;
		private $_tmpName;
		private $_types;

		private $_imageProcessingTypes;
		private $_maxWidth;
		private $_maxHight;
		private $_targetImage;
		private $_imageSource;
		private $_sourceWidth;
		private $_sourceHeight;
		private $_destWidth;
		private $_destHeight;
		private $_scale;
		private $_imgLoaders;
		private $_imgCreators;
		
		function __construct($fileSavePath) {
		  
		  if (!empty($fileSavePath)) {
		    $this->_fileSavePath = $fileSavePath;
		  } else {
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
		 * @param string $fileSaveName 
		 * @param string $imageProcessingType 
		 * @param string $maxWidth 
		 * @param string $maxHeight 
		 * @return void
		 * @author Lee Perry
		 */
		
		public function uploadImage($image, $filename, $imageProcessingType, $maxWidth = 0, $maxHeight = 0) {
				
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
					switch ($imageProcessingType) {					 
					  case self::COPY:
						  $this->_targetImage = $this->_imageSource;
					    break;

					  case self::RESIZE:
						  $this->_destWidth = $this->_maxWidth;
						  $this->_destHeight = $this->_maxHeight;
     					$this->createImage();
     			    break;
					  
					  case self::SCALE:
						  $this->scaleImage();
     					$this->createImage();
     			    break;
					}
				} else {
				 throw new Exception('Invalid image process type');
				}
				
				// Destination file.
				$this->_destFile = $this->_fileSavePath.'/'.$this->_filename.'.'.$this->_fileExtension;

				// Save image.
				imagejpeg ($this->_targetImage, $this->_destFile);
				
				// Free up memory
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
		
		private function scaleImage() {
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
		
		private function createImage() {
			// Create blank destination image.
			$this->_targetImage = imagecreatetruecolor($this->_destWidth, $this->_destHeight);

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
