<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>OTP</title>
</head>
<body>
    <p>Chào bạn,</p>

    <p>Bạn đang thực hiện xác thực để cập nhật thông tin chủ sở hữu cho sản phẩm:</p>
    <ul>
        <li>Product ID: {{ $product->id }}</li>
        <li>Tag ID: {{ $product->tag_id }}</li>
    </ul>

    <p><strong>Mã OTP của bạn là: {{ $otpCode }}</strong></p>
    <p>Mã có hiệu lực đến: {{ optional($expiresAt)->format('Y-m-d H:i:s') }}</p>

    <p>Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
</body>
</html>
