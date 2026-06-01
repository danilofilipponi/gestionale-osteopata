export interface ItalianCity {
  name: string;
  province: string;
  zip: string;
  cadastralCode: string;
}

// Dataset dimostrativo. In produzione sostituire con il dataset completo ISTAT.
export const italianCities: ItalianCity[] = [
  { name: "Milano", province: "MI", zip: "20121", cadastralCode: "F205" },
  { name: "Monza", province: "MB", zip: "20900", cadastralCode: "F704" },
  { name: "Bergamo", province: "BG", zip: "24121", cadastralCode: "A794" },
  { name: "Como", province: "CO", zip: "22100", cadastralCode: "C933" },
  { name: "Lecco", province: "LC", zip: "23900", cadastralCode: "E507" },
  { name: "Roma", province: "RM", zip: "00118", cadastralCode: "H501" },
  { name: "Torino", province: "TO", zip: "10121", cadastralCode: "L219" },
  { name: "Bologna", province: "BO", zip: "40121", cadastralCode: "A944" },
  { name: "Firenze", province: "FI", zip: "50121", cadastralCode: "D612" },
  { name: "Napoli", province: "NA", zip: "80121", cadastralCode: "F839" },
  { name: "Verona", province: "VR", zip: "37121", cadastralCode: "L781" },
  { name: "Varese", province: "VA", zip: "21100", cadastralCode: "L682" },
  { name: "Pavia", province: "PV", zip: "27100", cadastralCode: "G388" },
];

export const findCity = (name: string) => italianCities.find((city) => city.name.toLowerCase() === name.trim().toLowerCase());
