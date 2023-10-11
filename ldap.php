<?php
/* change password */
/* Тут вопрос, какой алгоритм шифрования используется в нашей LDAP-системе (например, SHA-1, MD5, SSHA и т. д.), чтобы правильно сравнивать хеши паролей. */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получите JSON-данные из тела запроса
    $json = file_get_contents('php://input');

    // Расшифруйте JSON-данные
    $data = json_decode($json, true);

    // Проверьте, что JSON был успешно расшифрован
    if ($data === null) {
        // Ошибка в данных JSON
        echo "Ошибка в данных JSON.";
        exit;
    }

    $oldPassword = $data['oldPassword'];
    $newPassword = $data['newPassword'];

    // Ваши операции по смене пароля в LDAP
    $ldap_server = 'ldaps://ip'; // Замените 'ip' на IP-адрес вашего LDAP-сервера
    $ldap_user = 'cn=admin,dc=example,dc=com'; // Замените на допустимое имя пользователя администратора LDAP
    $ldap_password = 'ваш_пароль'; // Замените на пароль администратора LDAP
    $ldap_dn = 'cn=пользователь,ou=группа,dc=example,dc=com'; // Замените на DN пользователя, у которого нужно сменить пароль

    $ldap_conn = ldap_connect($ldap_server);

    if ($ldap_conn) {
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

        if (ldap_bind($ldap_conn, $ldap_user, $ldap_password)) {
            // Привязка к LDAP-серверу под администратором прошла успешно

          // Получаем хеш старого пароля из LDAP
            $user_info = ldap_search($ldap_conn, $ldap_dn, "(objectClass=*)");
            $user_entry = ldap_first_entry($ldap_conn, $user_info);
            $user_password = ldap_get_values($ldap_conn, $user_entry, "userpassword")[0];

            // Разбираем хеш пароля, извлекаем соль и хеш
            list($password_hash, $salt) = explode('$', $user_password);

            // Проверяем старый пароль
            $oldPasswordHash = '{SSHA}' . base64_encode(sha1($oldPassword . base64_decode($salt), true));
            
            if ($oldPasswordHash === $user_password) {

            // Создаем запись изменения пароля
            $entry = [
                'userpassword' => '{SHA}' . base64_encode(sha1($newPassword, true)),
            ];

            if (ldap_modify($ldap_conn, $ldap_dn, $entry)) {
                echo "Пароль пользователя успешно изменен.";
            } else {
                echo "Ошибка при изменении пароля: " . ldap_error($ldap_conn);
            }
            } else {
              echo 'Ошибка, старый пароль не верен.';
            }
        } else {
            echo "Ошибка при привязке к LDAP-серверу: " . ldap_error($ldap_conn);
        }

        ldap_unbind($ldap_conn);
    } else {
        echo "Не удалось подключиться к LDAP-серверу.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Смена пароля</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <button id="openModal">Открыть форму смены пароля</button>
    
    <div id="changePasswordModal" style="display: none;">
        <h2>Смена пароля</h2>
        <form id="changePasswordForm">
            <label for="oldPassword">Старый пароль:</label>
            <input type="password" id="oldPassword" name="oldPassword"><br><br>

            <label for="newPassword">Новый пароль:</label>
            <input type="password" id="newPassword" name="newPassword"><br><br>
            
            <label for="confirmPassword">Подтвердите пароль:</label>
            <input type="password" id="confirmPassword" name="confirmPassword"><br><br>
            
            <button type="submit">Изменить пароль</button>
        </form>
    </div>

    <div id="resultMessage"></div>

    <script>
        $(document).ready(function() {
            $("#changePasswordForm").submit(function(e) {
                e.preventDefault();
                var oldPassword = $("#oldPassword").val();
                var newPassword = $("#newPassword").val();
                var confirmPassword = $("#confirmPassword").val();

                if (newPassword !== confirmPassword) {
                    $("#resultMessage").html("Пароли не совпадают.");
                    return;
                }

                if (newPassword.length < 8 || !/[a-zA-Z]/.test(newPassword) || !/\d/.test(newPassword) || !/[^a-zA-Z\d]/.test(newPassword)) {
                    $("#resultMessage").html("Новый пароль должен содержать не менее 8 символов, включать буквы, цифры и символы.");
                    return;
                }

                var requestData = {
                    oldPassword: oldPassword,
                    newPassword: newPassword,
                };

                $.ajax({
                    type: "POST",
                    url: "change_password.php",
                    data: JSON.stringify(requestData), // Преобразование в JSON
                    contentType: "application/json; charset=utf-8", // Заголовок Content-Type
                    success: function(response) {
                        $("#resultMessage").html(response);
                    }
                });
            });
        });
    </script>
</body>
</html>
