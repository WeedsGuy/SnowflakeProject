# Storefront Project >
## Store>
Index.html [ This is the opening landing page, allows users to input address. The address is checked if it's in the CSV 
of servicable properties. Displays google maps api with sq footage if available. Proceed to " mergedproject " > store.html

Store.html ( select serviced. Prices are based from pricechart.txt in data folder. customer address should carry
over through all pages )

card.html ( customer input fields, Everfault api to encrypt card number, stored in AWS Dynamo DB )

Can clean and refactor as needed. 

Minimal working setup commands

composer install --no-dev -o
( cd mergedproject && composer install --no-dev -o )

