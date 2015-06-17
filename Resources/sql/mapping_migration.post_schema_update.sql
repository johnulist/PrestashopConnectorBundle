INSERT pim_prestashop_attribute_mapping (id, attribute_id, prestashop_url, prestashop_attribute_id)
SELECT id, attribute_id, prestashop_url, prestashop_attribute_id
FROM pim_prestashop_attribute_mapping_old;

INSERT pim_prestashop_category_mapping (id, category_id, prestashop_url, prestashop_category_id)
SELECT id, category_id, prestashop_url, prestashop_category_id
FROM pim_prestashop_category_mapping_old;

INSERT pim_prestashop_family_mapping (id, family_id, prestashop_url, prestashop_family_id)
SELECT id, family_id, prestashop_url, prestashop_family_id
FROM pim_prestashop_family_mapping_old;

DROP TABLE pim_prestashop_attribute_mapping_old;
DROP TABLE pim_prestashop_category_mapping_old;
DROP TABLE pim_prestashop_family_mapping_old;
