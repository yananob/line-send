<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line Sender</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        @if(!empty($notification))
        <div class="alert alert-primary" role="alert">{{ $notification }}</div>
        @endif

        <form method="POST" action="{{ $baseUrl }}">
            <div class="mb-3">
                <label class="form-label">Target:</label><br>
                @foreach($targets as $target)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="target" id="{{ $target }}" value="{{ $target }}"
                        required>
                    <label class="form-check-label" for="{{ $target }}">{{ $target }}</label>
                </div>
                @endforeach
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">Message:</label>
                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
            </div>

            <input type="hidden" name="source" value="form">

            <button type="submit" class="btn btn-primary">Send</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
