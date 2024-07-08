CREATE DATABASE CONTENT
CREATE TABLE ORDER{
    id INTEGER AUTO_INCREMENT PRIMARY KEY;
    prix float,
    nom VARCHAR(100),
    categorie VARCHAR(50),
    quantite INT,
    Customer_mail VARCHAR(50)
}