import Link from "next/link"

export default function Footer() {
    return (
        <div className="flex items-center w-full bg-primary-dark h-auto ">
            <div className="flex flex-col md:flex-row justify-between w-full px-4 md:px-0">
                <svg width="250" height="137" viewBox="0 0 71 39" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M45.8005 2.38798L57.3697 9.90284e-05L36.6931 9.87818e-05C36.6931 2.38833 34.6111 5.19269 30.5349 5.19269C26.4587 5.19269 24.5203 1.55257 24.5203 9.86367e-05L20.3242 9.85866e-05C20.3242 9.85866e-05 18.406 4.63693 10.2536 6.62684C2.10119 8.61675 1.02219 4.41804 1.62163 2.9256C0.302854 3.46321 -1.93182 8.83168 3.30007 10.0296C8.53195 11.2275 13.8502 9.37292 17.2071 7.5223C14.7494 10.1486 8.21549 15.3426 6.0575 25.6106C3.89951 35.8787 8.75499 40.8334 15.1091 36.5348C7.67599 36.995 7.07655 28.3568 11.0928 21.5513C17.7607 10.2527 26.3786 10.1486 26.3786 10.1486L21.2234 23.2223L30.8744 10.7456C30.8744 10.7456 35.61 24.7153 30.2749 30.9238C24.9399 37.1323 19.1853 32.655 19.7248 29.491C20.2643 26.3271 23.0816 26.5957 24.3405 27.2225C26.2587 28.1777 26.4385 30.685 24.1606 31.3417C25.2996 32.2968 28.2368 30.2074 27.038 27.4613C25.3277 23.544 20.3242 23.8795 18.3461 26.5062C16.3679 29.1329 16.6077 32.9586 20.2043 35.8787C22.8992 38.0667 27.5775 38.2666 32.0133 33.6699C36.4492 29.0732 38.1047 21.8497 35.7898 8.05922C40.3541 14.8017 44.7814 19.9389 50.0565 23.6999C55.3316 27.4608 62.2252 29.9558 66.0616 28.595C69.898 27.2343 71.2831 23.6217 69.4784 16.4168C69.6558 21.9851 66.0506 25.618 59.5277 22.8641C53.0048 20.1102 47.1853 15.3553 41.7243 5.91013C50.2363 3.99981 57.1078 8.59648 56.2907 11.9992C55.6313 14.7453 52.8139 14.805 51.4952 13.5514C50.1764 12.2977 51.2554 9.37255 53.7731 11.4619C53.269 10.1492 51.7949 9.49193 50.596 10.1486C49.3971 10.8053 48.8191 12.7811 50.4162 14.4468C52.0133 16.1126 53.8343 15.9247 55.1518 15.5811C57.6694 14.9244 58.2255 12.7156 58.2689 11.4619C58.4445 6.38768 51.6028 3.14233 45.8005 2.38798Z" fill="#c98520" />
                </svg>
                <div className="flex flex-col gap-4 pt-8">
                    <div className="flex items-center gap-2">
                        <span className="text-4xl font-bold text-primary-text font-young">#</span>
                        <div>
                            <p className="font-bold text-white leading-tight font-young">Samarinda Hash</p>
                            <p className="text-xs text-white">House Harriers</p>
                        </div>
                    </div>
                    <div className="text-sm font-medium text-wrap md:text-md text-white">
                        Jalan AM. Sangaji No.10 Samarinda, 75117
                    </div>
                </div>
                <div className="flex flex-col gap-4 pt-8">
                    <div className="font-bold text-lg text-white">
                        Tautan Cepat
                    </div>
                    <ol className="list-none flex flex-col gap-1 text-white">
                        <li>
                            <Link href="/about" className="hover:text-white">
                                Tentang SH3
                            </Link>
                        </li>
                        <li>
                            <Link href="/members" className="hover:text-white">
                                Member
                            </Link>
                        </li>
                        <li>
                            <Link href="/gallery" className="hover:text-white">
                                Gallery
                            </Link>
                        </li>
                        <li>
                            <Link href="/event" className="hover:text-white">
                                Events
                            </Link>
                        </li>
                    </ol>
                </div>
                <div className="flex flex-col gap-4 pt-8">
                    <div className="font-bold text-lg text-white">
                        Hubungi Kami
                    </div>
                    <ol className="list-none flex flex-col gap-1 text-white">
                        <li>
                            <Link href="mailto:hutanhijausamarindahidup@gmail.com" className="hover:text-white">
                                Email - <span>hutanhijausamarindahidup@gmail.com</span>
                            </Link>
                        </li>
                        <li>
                            <Link href="http://wa.me/+62811588338" className="hover:text-white">
                                Phone - <span>0811588338</span>
                            </Link>
                        </li>
                        <li>
                            <Link href="https://www.instagram.com/samarinda_hashhouseharriers/" className="hover:text-white">
                                Instagram - <span>@samarinda_hashhouseharriers</span>
                            </Link>
                        </li>
                        <li>
                            <Link href="https://www.facebook.com/samarinda.hash.house.harriers.2025" className="hover:text-white">
                                Facebook - <span>Samarinda Hash House Harriers</span>
                            </Link>
                        </li>
                    </ol>
                </div>

                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.6722277791782!2d117.15034347582339!3d-0.4903164352759345!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2df67f6cf7d346a5%3A0x48179c10cdd6f405!2sJl.%20A.M.%20Sangaji%20No.10%2C%20Bandara%2C%20Kec.%20Sungai%20Pinang%2C%20Kota%20Samarinda%2C%20Kalimantan%20Timur%2075242!5e0!3m2!1sen!2sid!4v1782145994021!5m2!1sen!2sid"
                    height="250" loading="lazy" referrerPolicy="no-referrer-when-downgrade"
                    className="w-full md:w-80 p-4 pt-8"></iframe>


            </div>

        </div>
    )
}