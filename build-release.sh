#!/bin/bash

# Check if version is provided
if [ "$#" -ne 1 ]; then
    echo "Error: Version number is required."
    echo "Usage: $0 <version>"
    exit 1
fi

# Assign the version from the command line argument
version="$1"

# Get the script's directory and ensure we operate from it
script_dir=$(realpath "$(dirname "$0")")
cd "$script_dir" || { echo "Failed to change directory to script location"; exit 1; }

# Ensure npm is available
if ! command -v npm >/dev/null 2>&1; then
    echo "Error: npm is required to build assets before packaging."
    exit 1
fi

# Install dependencies if needed
if [ ! -d "node_modules" ]; then
    echo "Installing npm dependencies..."
    npm install || { echo "npm install failed"; exit 1; }
fi

# Build/minify assets
echo "Running npm build..."
npm run build || { echo "npm run build failed"; exit 1; }

# Define the list of files and folders to include
include_list=("LICENSE" "readme.txt" "kd-quiz.php" "includes" "assets" "languages")

# Define the top-level folder name inside the zip
top_level_folder_name="kd-quiz"

# Temp directory path
root_temp_dir=$(mktemp -d)
temp_dir="${root_temp_dir}/${top_level_folder_name}"

# Output directory for the zip file (inside the script's directory)
output_dir="${script_dir}/releases"

# Create the temp directory
mkdir -p "$temp_dir"

# Create the output directory
mkdir -p "$output_dir"

# Copy the files and folders to the temp directory
for item in "${include_list[@]}"; do
    cp -r "${script_dir}/${item}" "$temp_dir/"
done

# Remove existing archives
rm -f "${output_dir}/${top_level_folder_name}-${version}.zip"

# Navigate to the temp directory
cd "${root_temp_dir}" || { echo "Failed to change directory"; exit 1; }

# Create the ZIP file with the version suffix and exclude hidden files/folders
zip -r "${output_dir}/${top_level_folder_name}-${version}.zip" ${top_level_folder_name} -x "*/.*" -x ".*"

# Navigate back to the original directory
cd - || { echo "Failed to change directory"; exit 1; }

# Clean up: Remove the temporary directory
rm -rf "$root_temp_dir"

echo "ZIP archive created in $output_dir/${top_level_folder_name}-${version}.zip"
