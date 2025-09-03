#!/bin/bash

# Update blog plugin to use direct file upload instead of file manager

# Replace button text
sed -i 's/Select Image/Upload Image/g' plugins/blog/blog.php

# Replace function name
sed -i 's/openFileManager/openFileUpload/g' plugins/blog/blog.php

# Replace the file manager JavaScript with direct upload
sed -i '/window.open.*filemanager/,/});/c\
                const input = document.createElement("input");\
                input.type = "file";\
                input.accept = "image/*";\
                input.click();\
                \
                input.onchange = function() {\
                    const file = input.files[0];\
                    if (file) {\
                        const formData = new FormData();\
                        formData.append("action", "upload_image");\
                        formData.append("image", file);\
                        \
                        fetch("?action=upload_image", {\
                            method: "POST",\
                            body: formData\
                        })\
                        .then(response => response.json())\
                        .then(data => {\
                            if (data.success) {\
                                document.querySelector("input[name=\x27featured_image\x27]").value = data.url;\
                                const previewContainer = document.querySelector("input[name=\x27featured_image\x27]").closest("div").nextElementSibling;\
                                if (previewContainer) {\
                                    previewContainer.innerHTML = `<img src="${data.url}" alt="Featured image preview" class="max-w-xs h-auto">`;\
                                } else {\
                                    const newPreview = document.createElement("div");\
                                    newPreview.className = "mt-2";\
                                    newPreview.innerHTML = `<img src="${data.url}" alt="Featured image preview" class="max-w-xs h-auto">`;\
                                    document.querySelector("input[name=\x27featured_image\x27]").closest("div").after(newPreview);\
                                }\
                            } else {\
                                alert("Upload failed: " + (data.error || "Unknown error"));\
                            }\
                        })\
                        .catch(error => {\
                            alert("Upload failed: " + error);\
                        });\
                    }\
                };' plugins/blog/blog.php

echo "Blog plugin updated successfully!"
