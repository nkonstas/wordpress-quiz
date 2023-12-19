#!/bin/bash

# Check if version is provided
if [ "$#" -ne 1 ]; then
    echo "Error: Version number is required."
    echo "Usage: $0 <version>"
    exit 1
fi

# Assign the version from the command line argument
version="$1"

# Define the list of files and folders to include
include_list=("LICENSE" "kd-quiz.php" "includes" "assets")

# Define the top-level folder name inside the zip
top_level_folder_name="kd-quiz"

# Temp directory path
temp_dir="/tmp/${top_level_folder_name}"

# Get the script's directory
script_dir="$(dirname "$0")"

# Output directory for the zip file (inside the script's directory)
output_dir="${script_dir}/releases"

# Lock file location
lock_file="/tmp/${top_level_folder_name}.lock"

# Check for lock file
if [ -e "$lock_file" ]; then
    echo "Script is already running."
    exit 1
fi

# Create lock file
touch "$lock_file"

# Ensure removal of the existing temp directory, if it exists
rm -rf "$temp_dir"

# Create the temp directory
mkdir -p "$temp_dir"

# Create the output directory
mkdir -p "$output_dir"

# Copy the files and folders to the temp directory
for item in "${include_list[@]}"; do
    cp -r "$item" "$temp_dir/"
done

# Remove existing archives
rm -f "${output_dir}/${top_level_folder_name}-${version}.zip"

# Create the ZIP file with the version suffix and exclude hidden files/folders
zip -r "${output_dir}/${top_level_folder_name}-${version}.zip" "$temp_dir" -x "*/.*" -x ".*"

# Clean up: Remove the temporary directory and lock file
rm -rf "$temp_dir"
rm -f "$lock_file"

echo "ZIP archive created in $output_dir/${top_level_folder_name}-${version}.zip"
