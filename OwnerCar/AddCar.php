
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Car</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: white;
            width: 520px;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .icon {
            width: 90px;
            height: 60px;
            margin: auto;
            background: #f0f0ff;
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 35px;
        }

        h1 {
            text-align: center;
            margin-top: 25px;
            color: #1f2937;
        }

        .subtitle {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 30px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #374151;
        }

        input {
            width: 100%;
            height: 42px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #6c63ff;
        }

        .row {
            display: flex;
            gap: 15px;
        }

        .row div {
            width: 50%;
        }

        .file-input {
            padding: 8px;
            height: auto;
        }

        button {
            width: 100%;
            height: 45px;
            background: #6c63ff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #584ff0;
        }

    </style>
</head>


<body>

<div class="container">

    <div class="icon">
        🚗
    </div>

    <h1>Add Car</h1>

    <p class="subtitle">
        Please provide your car details below.
    </p>


    <form action="StoreCarDataToDB.php" method="POST" enctype="multipart/form-data">

        <label>Car Make *</label>
        <input type="text" name="car_make" placeholder="Example: Toyota" required>

        <label>Car Model *</label>
        <input type="text" name="car_model" placeholder="Example: Corolla" required>

        <div class="row">
            <div>
                 <label>Model Year *</label>    
                <input                     
                       type="number"
                       min = 1900
                       name="car_model_year" 
                       placeholder="2024"
                       required>
            </div>

            <div>
                <label>Color *</label>
                <input type="text" 
                       name="car_color"
                       placeholder="Black"
                       required>
            </div>
        </div>

        <label>Plate Number *</label>
        <input type="text"
               name="car_plate_number"
               placeholder="ABC-1234"
               required>

        <label>Daily Rent Price *</label>
        <input type="number"
               min="0"
               name="car_daily_rent"
               placeholder="Enter daily rent"
               required>

        <label>Car Image *</label>

        <input type="file"
               name="car_image"
               class="file-input"
               accept="image/*"
               required>

        <button type="submit">
            Add Car
        </button>


    </form>

</div>


</body>
</html>
