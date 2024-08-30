# coding-partners/auto-controller

## Description
`coding-partners/auto-controller` is a Laravel package allows you to quickly create fully functional controllers for any model in your project. With a single command, the package will create a console with all standard CRUD methods (create, read, update, delete) as well as soft-delete methods, saving you a lot of time and effort. This enables you to focus on building the core features of your project. And let's not forget its support for uploading all types of files with high security


## Installation

To install the package, use the following command via Composer:

```bash
composer require coding-partners/auto-controller
```

## Requirements

- PHP version 8.0 or higher is required.

## Usage

To use the package:
 You must first set up the model and migration.
 For file fields:
  - You must add the suffix `_img` to the image field. EX: image_img
  - You must add the suffix `_vid` to the video field. EX: video_vid
  - You must add the suffix `_aud` to the audio field, EX: audio_aud
  - You must add the suffix `_docs` to the document, EX: file_docs

  ```php
      public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('image_img');
            $table->string('video_vid');
            $table->string('audio_aud');
            $table->string('file_docs');
            $table->timestamps();
        });
    }
  ```

After ensuring that you have performed all the previous steps, you can run the following command via the Artisan Console:

```bash
php artisan crud:generate ModelName
```

### Workflow:
1. The command will generate `FormRequest` classes for the Store and Update operations.
2. It will then generate an API `Resource` file for the model.
3. You will be prompted to choose whether to add a Service layer:
    - If you answer `yes`, a Service file specific to the controller will be generated, followed by the controller.
    - If you answer `no`, only the controller will be generated.
4. Finally, the necessary routes will be added to the `api.php` file.


#### Example with Service
```console
EUROPELAPTOP@DESKTOP-P3SVV3J MINGW64 /d/CRUD
$ php artisan crud:generate Post
Generating store FormRequest for Post...
FormRequest StorePostRequest created successfully in folder PostRequest.
Generating update FormRequest for Post...
FormRequest UpdatePostRequest created successfully in folder PostRequest.

 Do you want to generate a Service for Post? (yes/no) [no]:
 > yes

Generating Service for Post...
Service PostService created successfully.
Generating CRUD with service for Post...
Controller PostController created successfully.
Generating routes/api.php for Post...
Post Route added successfully.
```

#### Example without Service
```console
EUROPELAPTOP@DESKTOP-P3SVV3J MINGW64 /d/CRUD
$ php artisan crud:generate User
Generating store FormRequest for User...
FormRequest StoreUserRequest created successfully in folder UserRequest.
Generating update FormRequest for User...
FormRequest UpdateUserRequest created successfully in folder UserRequest.
Generating Resource for User...
Resource UserResource created successfully.

 Do you want to generate a Service for User? (yes/no) [no]:
 >

Generating CRUD without service for User...
Controller UserController created successfully.
Generating routes/api.php for User...
User Route added successfully.
```

## License

This package is licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Contributions

Contributions are welcome! If you'd like to contribute to the development of this package, you can start by opening an `Issue` or submitting a `Pull Request`.

## Authors

- YousefSaleh1
- Ayham-Ibrahim
```
