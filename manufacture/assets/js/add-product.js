// Product Add Page JavaScript
// Global variables
let uploadedFiles = new Set();

document.addEventListener('DOMContentLoaded', function() {
    initializeProductForm();
    initializeSidebar();
});

function initializeProductForm() {
    // Only run on add-product page
    if (!window.location.pathname.includes('add.php')) {
        return;
    }
    
    // Reset product folder for new product
    fetch('add.php?action=reset_product_folder')
    .then(response => response.json())
    .then(data => {
        //console.log('Product folder reset:', data.message);
    })
    .catch(error => {
        console.error('Error resetting product folder:', error);
    });
    
    // Reset uploaded files tracking
    uploadedFiles.clear();
    
    // Initialize all form components
    initializeImageUpload();
    initializeCategorySearch();
    initializeColorSelection();
    initializeTagsInput();
}

function initializeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (sidebar && mainContent && toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('collapsed');
        }
    }
}

// Image Upload
function initializeImageUpload() {
    const uploadSection = document.getElementById('imageUploadSection');
    const fileInput = document.getElementById('productImages');

    //console.log('Initializing image upload:', { uploadSection, fileInput });

    if (uploadSection && fileInput) {
        // Click to upload - prevent multiple triggers
        uploadSection.addEventListener('click', (e) => {
            // Don't trigger if clicking on the file input itself
            if (e.target === fileInput || e.target.closest('input[type="file"]')) {
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();
            
            // Add visual feedback
            uploadSection.style.backgroundColor = '#e3f2fd';
            setTimeout(() => {
                uploadSection.style.backgroundColor = '';
            }, 200);
            
            // Trigger file input click
            fileInput.click();
        });
        
        uploadSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadSection.classList.add('dragover');
        });

        uploadSection.addEventListener('dragleave', () => {
            uploadSection.classList.remove('dragover');
        });

        uploadSection.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            handleFileUpload(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', (e) => {
            //console.log('File input changed:', e.target.files);
            if (e.target.files.length > 0) {
                handleFileUpload(e.target.files);
                // Clear the input to allow selecting the same file again
                e.target.value = '';
            }
        });
        
        // Prevent file input from triggering upload section click
        fileInput.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
    // Removed error logging since this function might be called on pages without image upload
}

function handleFileUpload(files) {
    const formData = new FormData();
    const manufacturerId = document.getElementById('manufacturerId').value;
    const productId = document.getElementById('productId').value || 'temp';

    //console.log('Handling file upload:', files.length, 'files');

    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            formData.append('images[]', file);
        }
    });

    if (formData.has('images[]')) {
        formData.append('manufacturer_id', manufacturerId);
        formData.append('product_id', productId);
        uploadImages(formData);
    } else {
        console.log('No valid image files found');
    }
}

// Track uploaded files to prevent duplicates

function uploadImages(formData) {
    fetch('add.php?action=upload_images', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        //console.log('Upload response:', data);
        if (data.success) {
            data.images.forEach(image => {
                // Check if this image was already uploaded
                if (!uploadedFiles.has(image.id)) {
                    uploadedFiles.add(image.id);
                    addImagePreview(image);
                } else {
                    //console.log('Image already uploaded:', image.id);
                }
            });
            showNotification('Images uploaded successfully!', 'success');
        } else {
            showNotification('Error uploading images: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error uploading images', 'error');
    });
}

function addImagePreview(image) {
    const previewContainer = document.getElementById('imagePreviewContainer');
    const isFirstImage = previewContainer.children.length === 0;

    //console.log('Adding image preview:', image);

    const imageItem = document.createElement('div');
    imageItem.className = `image-preview-item ${isFirstImage ? 'primary' : ''}`;
    imageItem.dataset.imageId = image.id;
    imageItem.dataset.imagePath = image.path;

    // Handle existing vs new images
    let imageUrl = image.path;
    if (image.path.startsWith('existing_')) {
        // Extract the actual path for existing images
        const parts = image.path.split('|');
        if (parts.length >= 2) {
            imageUrl = parts[1];
        }
    }
    //console.log('Image URL:', imageUrl);

    imageItem.innerHTML = `
        <img src="${imageUrl}" alt="Product Image" 
             onclick="setMainImage('${image.id}')"
             style="cursor: pointer;" 
             onerror=this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjVGNUY1Ii8+Cjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SW1hZ2U8L3RleHQ+Cjwvc3ZnPgo='">
        <div class="image-actions">
            <button type="button" class="image-action-btn delete" onclick="deleteImage('${image.id}')" title="Delete image">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

    previewContainer.appendChild(imageItem);

    if (isFirstImage) {
        document.getElementById('mainImage').value = image.path;
    }
    
    // Test image accessibility
    testImageAccess(image.relative_path);
}

function testImageAccess(imagePath) {
    fetch(`add.php?action=test_image_access&path=${encodeURIComponent(imagePath)}`)
    .then(response => response.json())
    .then(data => {
        //console.log('Image access test:', data);
    })
    .catch(error => {
        console.error('Error testing image access:', error);
    });
}

function setMainImage(imageId) {
    // Remove "Main" text and star from all images
    document.querySelectorAll('.image-preview-item').forEach(item => {
        item.classList.remove('primary');
    });
    
    // Add "Main" text to the clicked image
    const clickedImage = document.querySelector(`[data-image-id="${imageId}"]`);
    if (clickedImage) {
        clickedImage.classList.add('primary');
        document.getElementById('mainImage').value = clickedImage.dataset.imagePath;
        showNotification('Main image updated successfully!', 'success');
    }
    
    // For now, we'll handle the server update when the product is saved
    // The main image will be determined by which image has the "primary" class
}

function deleteImage(imageId) {
    if (!confirm('Are you sure you want to delete this image?')) return;

    // Check if this is an existing image (has numeric ID) or new image
    const imageElement = document.querySelector(`[data-image-id="${imageId}"]`);
    if (!imageElement) return;

    const imagePath = imageElement.dataset.imagePath;
    
    if (imagePath.startsWith('existing_')) {
        // Existing image - delete from server
        const parts = imagePath.split('|');
        const dbImageId = parts[0].replace('existing_', '');
        
        fetch('add.php?action=delete_image', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image_id: dbImageId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                imageElement.remove();
                showNotification('Image deleted successfully!', 'success');
            } else {
                showNotification('Error deleting image: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting image', 'error');
        });
    } else {
        // New image - just remove from DOM
        imageElement.remove();
        showNotification('Image removed successfully!', 'success');
    }
}

// Category Search
function initializeCategorySearch() {
    const categorySearch = document.getElementById('categorySearch');
    const subcategorySearch = document.getElementById('subcategorySearch');
    let searchTimeout;

    if (categorySearch) {
        // Show all categories when input is focused
        categorySearch.addEventListener('focus', function() {
            if (this.value.trim().length === 0) {
                searchCategories('');
            }
        });

        categorySearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            searchTimeout = setTimeout(() => searchCategories(query), 300);
        });
    }

    if (subcategorySearch) {
        // Show all subcategories when input is focused (if category is selected)
        subcategorySearch.addEventListener('focus', function() {
            const categoryId = document.getElementById('selectedCategoryId')?.value;
            if (categoryId && this.value.trim().length === 0) {
                searchSubcategories('', categoryId);
            }
        });

        subcategorySearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            const categoryId = document.getElementById('selectedCategoryId')?.value;
            
            if (categoryId) {
                searchTimeout = setTimeout(() => searchSubcategories(query, categoryId), 300);
            } else {
                const subcategoryResults = document.getElementById('subcategoryResults');
                if (subcategoryResults) {
                    subcategoryResults.innerHTML = '';
                }
            }
        });
    }

    // Only add click listener if we're on a page with category search
    if (categorySearch || subcategorySearch) {
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.category-search-container')) {
                const categoryResults = document.getElementById('categoryResults');
                const subcategoryResults = document.getElementById('subcategoryResults');
                
                if (categoryResults) {
                    categoryResults.style.display = 'none';
                }
                if (subcategoryResults) {
                    subcategoryResults.style.display = 'none';
                }
            }
        });
    }
}

function searchCategories(query) {
    fetch(`add.php?action=search_categories&q=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
        const resultsContainer = document.getElementById('categoryResults');
        resultsContainer.innerHTML = '';

        //console.log('Category search response:', data);

        if (data.error) {
            resultsContainer.innerHTML = `<div class="search-result-item">Error: ${data.error}</div>`;
        } else if (Array.isArray(data) && data.length > 0) {
            data.forEach(category => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                item.textContent = category.name;
                item.addEventListener('click', () => selectCategory(category));
                resultsContainer.appendChild(item);
            });
        } else {
            resultsContainer.innerHTML = '<div class="search-result-item">No categories found</div>';
        }
        
        // Show results container
        if (resultsContainer) {
            resultsContainer.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error searching categories:', error);
        const resultsContainer = document.getElementById('categoryResults');
        if (resultsContainer) {
            resultsContainer.innerHTML = '<div class="search-result-item">Error loading categories</div>';
            resultsContainer.style.display = 'block';
        }
    });
}

function searchSubcategories(query, categoryId) {
    fetch(`add.php?action=search_subcategories&q=${encodeURIComponent(query)}&category_id=${categoryId}`)
    .then(response => response.json())
    .then(data => {
        const resultsContainer = document.getElementById('subcategoryResults');
        resultsContainer.innerHTML = '';

        //console.log('Subcategory search response:', data);

        if (data.error) {
            resultsContainer.innerHTML = `<div class="search-result-item">Error: ${data.error}</div>`;
        } else if (Array.isArray(data) && data.length > 0) {
            data.forEach(subcategory => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                item.textContent = subcategory.name;
                item.addEventListener('click', () => selectSubcategory(subcategory));
                resultsContainer.appendChild(item);
            });
        } else {
            resultsContainer.innerHTML = '<div class="search-result-item">No subcategories found</div>';
        }
        
        // Show results container
        if (resultsContainer) {
            resultsContainer.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error searching subcategories:', error);
        const resultsContainer = document.getElementById('subcategoryResults');
        if (resultsContainer) {
            resultsContainer.innerHTML = '<div class="search-result-item">Error loading subcategories</div>';
            resultsContainer.style.display = 'block';
        }
    });
}

function selectCategory(category) {
    const selectedCategoryId = document.getElementById('selectedCategoryId');
    const selectedCategoryName = document.getElementById('selectedCategoryName');
    const categorySearch = document.getElementById('categorySearch');
    const categoryResults = document.getElementById('categoryResults');
    const selectedSubcategoryId = document.getElementById('selectedSubcategoryId');
    const selectedSubcategoryName = document.getElementById('selectedSubcategoryName');
    const subcategorySearch = document.getElementById('subcategorySearch');
    
    if (selectedCategoryId) selectedCategoryId.value = category.id;
    if (selectedCategoryName) selectedCategoryName.value = category.name;
    if (categorySearch) categorySearch.value = category.name;
    if (categoryResults) categoryResults.style.display = 'none';
    
    // Clear subcategory
    if (selectedSubcategoryId) selectedSubcategoryId.value = '';
    if (selectedSubcategoryName) selectedSubcategoryName.value = '';
    if (subcategorySearch) {
        subcategorySearch.value = '';
        subcategorySearch.disabled = false;
        subcategorySearch.placeholder = 'Search for a subcategory...';
    }
    
    updateSelectedCategoriesDisplay();
}

function selectSubcategory(subcategory) {
    const selectedSubcategoryId = document.getElementById('selectedSubcategoryId');
    const selectedSubcategoryName = document.getElementById('selectedSubcategoryName');
    const subcategorySearch = document.getElementById('subcategorySearch');
    const subcategoryResults = document.getElementById('subcategoryResults');
    
    if (selectedSubcategoryId) selectedSubcategoryId.value = subcategory.id;
    if (selectedSubcategoryName) selectedSubcategoryName.value = subcategory.name;
    if (subcategorySearch) subcategorySearch.value = subcategory.name;
    if (subcategoryResults) subcategoryResults.style.display = 'none';
    
    updateSelectedCategoriesDisplay();
}

function updateSelectedCategoriesDisplay() {
    const container = document.getElementById('selectedCategories');
    const categoryId = document.getElementById('selectedCategoryId').value;
    const categoryName = document.getElementById('selectedCategoryName').value;
    const subcategoryId = document.getElementById('selectedSubcategoryId').value;
    const subcategoryName = document.getElementById('selectedSubcategoryName').value;

}

function removeCategory() {
    document.getElementById('selectedCategoryId').value = '';
    document.getElementById('selectedCategoryName').value = '';
    document.getElementById('categorySearch').value = '';
    document.getElementById('selectedSubcategoryId').value = '';
    document.getElementById('selectedSubcategoryName').value = '';
    document.getElementById('subcategorySearch').value = '';
    
    // Disable subcategory search
    document.getElementById('subcategorySearch').disabled = true;
    document.getElementById('subcategorySearch').placeholder = 'Select a category first...';
    
    updateSelectedCategoriesDisplay();
}

function removeSubcategory() {
    document.getElementById('selectedSubcategoryId').value = '';
    document.getElementById('selectedSubcategoryName').value = '';
    document.getElementById('subcategorySearch').value = '';
    updateSelectedCategoriesDisplay();
}

// Color Selection
function initializeColorSelection() {
    const colorItems = document.querySelectorAll('.color-item:not(.add-color-item)');

    if (colorItems.length > 0) {
        colorItems.forEach(item => {
            item.addEventListener('click', function() {
                const colorName = this.querySelector('.color-name')?.textContent;
                const colorCode = this.querySelector('.color-preview')?.style.backgroundColor;
                
                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    removeSelectedColor(colorName);
                } else {
                    this.classList.add('selected');
                    addSelectedColor(colorName, colorCode, false);
                }
            });
        });
    }
}

// Color Popup Functions
function openColorPopup() {
    document.getElementById('colorPopup').style.display = 'flex';
    document.getElementById('popupColorName').focus();
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Add click outside to close
    document.getElementById('colorPopup').addEventListener('click', function(e) {
        if (e.target === this) {
            closeColorPopup();
        }
    });
    
    // Add keyboard support
    document.addEventListener('keydown', handlePopupKeydown);
}

function handlePopupKeydown(e) {
    if (e.key === 'Escape') {
        closeColorPopup();
    } else if (e.key === 'Enter' && e.ctrlKey) {
        addCustomColor();
    }
}

function closeColorPopup() {
    document.getElementById('colorPopup').style.display = 'none';
    document.getElementById('popupColorName').value = '';
    document.getElementById('popupColorPicker').value = '#000000';
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Remove keyboard listener
    document.removeEventListener('keydown', handlePopupKeydown);
}

function closeColorPopup() {
    document.getElementById('colorPopup').style.display = 'none';
    document.getElementById('popupColorName').value = '';
    document.getElementById('popupColorPicker').value = '#000000';
    
    // Restore body scroll
    document.body.style.overflow = '';
}

function addCustomColor() {
    const colorName = document.getElementById('popupColorName').value.trim();
    const colorCode = document.getElementById('popupColorPicker').value;
    
    if (!colorName) {
        showNotification('Please enter a color name', 'error');
        return;
    }
    
    if (!colorCode) {
        showNotification('Please select a color', 'error');
        return;
    }
    
    // Check if color name already exists
    const existingColor = document.querySelector(`[data-color-name="${colorName}"]`);
    if (existingColor) {
        showNotification('A color with this name already exists', 'error');
        return;
    }
    
    // Add the custom color to the color selection
    addCustomColorToSelection(colorName, colorCode);
    
    // Add to selected colors
    addSelectedColor(colorName, colorCode, true);
    
    // Close popup and show success message
    closeColorPopup();
    showNotification('Custom color added successfully!', 'success');
}

function addCustomColorToSelection(colorName, colorCode) {
    const colorSelection = document.querySelector('.color-selection');
    const addColorItem = document.querySelector('.add-color-item');
    
    const newColorItem = document.createElement('div');
    newColorItem.className = 'color-item';
    newColorItem.dataset.colorName = colorName;
    newColorItem.innerHTML = `
        <div class="color-preview" style="background-color: ${colorCode};"></div>
        <div class="color-name">${colorName}</div>
    `;
    
    // Add click event listener
    newColorItem.addEventListener('click', function() {
        if (this.classList.contains('selected')) {
            this.classList.remove('selected');
            removeSelectedColor(colorName);
        } else {
            this.classList.add('selected');
            addSelectedColor(colorName, colorCode, true);
        }
    });
    
    // Insert before the add color button
    colorSelection.insertBefore(newColorItem, addColorItem);
}

function addSelectedColor(name, code, isCustom) {
    const selectedColors = document.getElementById('selectedColors');
    const colorId = 'color_' + Date.now();
    
    const colorTag = document.createElement('div');
    colorTag.className = 'selected-category';
    colorTag.id = colorId;
    colorTag.innerHTML = `
        <div style="width: 20px; height: 20px; background-color: ${code}; border-radius: 50%; border: 2px solid white;"></div>
        <span>${name}</span>
        <button type="button" class="remove-category" onclick="removeSelectedColor('${colorId}')">
            <i class="fas fa-times"></i>
        </button>
        <input type="hidden" name="colors[]" value='${JSON.stringify({name, code, isCustom})}'>
    `;
    
    selectedColors.appendChild(colorTag);
}

function removeSelectedColor(colorId) {
    const colorElement = document.getElementById(colorId);
    if (colorElement) colorElement.remove();
}

// Tags Input
function initializeTagsInput() {
    const tagsInput = document.getElementById('tagsInput');

    if (tagsInput) {
        tagsInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const tag = this.value.trim();
                if (tag) {
                    addTag(tag);
                    this.value = '';
                }
            }
        });

        tagsInput.addEventListener('blur', function() {
            const tag = this.value.trim();
            if (tag) {
                addTag(tag);
                this.value = '';
            }
        });
    }
}

function addTag(tagText) {
    const tagsList = document.getElementById('tagsList');
    if (!tagsList) return;
    
    const tagId = 'tag_' + Date.now();
    
    const tagItem = document.createElement('div');
    tagItem.className = 'tag-item';
    tagItem.id = tagId;
    tagItem.innerHTML = `
        <span>${tagText}</span>
        <button type="button" class="remove-tag" onclick="removeTag('${tagId}')">
            <i class="fas fa-times"></i>
        </button>
        <input type="hidden" name="tags[]" value="${tagText}">
    `;
    
    tagsList.appendChild(tagItem);
}

function removeTag(tagId) {
    const tagElement = document.getElementById(tagId);
    if (tagElement) tagElement.remove();
}

// Form Actions
function saveDraft() {
    const form = document.getElementById('productForm');
    const formData = new FormData(form);
    formData.append('action', 'save_draft');
    
    // Get all image paths
    const imageElements = document.querySelectorAll('.image-preview-item');
    imageElements.forEach((element, index) => {
        formData.append('images[]', element.dataset.imagePath);
    });
    
    // Get the main image from the primary class
    const primaryImage = document.querySelector('.image-preview-item.primary');
    if (primaryImage) {
        formData.set('main_image', primaryImage.dataset.imagePath);
    }

    fetch('add.php?action=save_product', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product saved as draft successfully!', 'success');
            if (data.product_id) {
                document.getElementById('productId').value = data.product_id;
            }
        } else {
            showNotification('Error saving draft: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving draft', 'error');
    });
}

function publishProduct() {
    if (validateForm()) {
        const form = document.getElementById('productForm');
        const formData = new FormData(form);
        formData.append('action', 'publish');
        
        // Get all image paths
        const imageElements = document.querySelectorAll('.image-preview-item');
        imageElements.forEach((element, index) => {
            formData.append('images[]', element.dataset.imagePath);
        });
        
        // Get the main image from the primary class
        const primaryImage = document.querySelector('.image-preview-item.primary');
        if (primaryImage) {
            formData.set('main_image', primaryImage.dataset.imagePath);
        }

        fetch('add.php?action=save_product', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Product published successfully!', 'success');
                setTimeout(() => {
                    window.location.href = 'list.php';
                }, 2000);
            } else {
                showNotification('Error publishing product: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error publishing product', 'error');
        });
    }
}

function validateForm() {
    let isValid = true;
    const errors = [];

    if (!document.getElementById('productName').value.trim()) {
        errors.push('Product name is required');
        isValid = false;
    }

    if (!document.getElementById('productDescription').value.trim()) {
        errors.push('Product description is required');
        isValid = false;
    }

    if (!document.getElementById('selectedCategoryId').value) {
        errors.push('Please select a category');
        isValid = false;
    }

    if (!document.getElementById('selectedSubcategoryId').value) {
        errors.push('Please select a subcategory');
        isValid = false;
    }

    if (document.querySelectorAll('.image-preview-item').length === 0) {
        errors.push('Please upload at least one product image');
        isValid = false;
    }

    if (document.querySelectorAll('input[name="sizes[]"]').length === 0) {
        errors.push('Please add at least one size with pricing');
        isValid = false;
    }

    if (document.querySelectorAll('input[name="colors[]"]').length === 0) {
        errors.push('Please select at least one color');
        isValid = false;
    }

    if (!isValid) {
        showNotification(errors.join('<br>'), 'error');
    }

    return isValid;
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type}`;
    notification.innerHTML = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.maxWidth = '500px';

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
} 