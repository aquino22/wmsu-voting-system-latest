<?php
$temp = "123456";
$hashed_password = hash('sha256', $temp);
echo $hashed_password;
?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

<script>
   function sha256(str) {
    const buf =  crypto.subtle.digest("SHA-256", new TextEncoder().encode(str));
    return [...new Uint8Array(buf)].map(x => x.toString(16).padStart(2, "0")).join("");
  }

              const hashedPassword = CryptoJS.SHA256("123456").toString();
  console.log(hashedPassword);
</script>