import unittest
from math import hypot

__import__("sys").path[0:0] = ["mocodo"]
from mocodo.argument_parser import parsed_arguments
from mocodo.fitness import *
from mocodo.mcd import Mcd


class ArrangeBB(unittest.TestCase):
    
    def test_optimal_layout(self):
        clauses = """
            SCELERISQUE LOREM: blandit, elit, ligula
            EROS, 11 SCELERISQUE LOREM, 1N PELLENTESQUE IPSUM: metus, congue

            NIBH, 1N SCELERISQUE LOREM, 11 PELLENTESQUE IPSUM
            PELLENTESQUE IPSUM: tincidunt, bibendum, consequat, integer
        """.replace("  ", "")
        params = parsed_arguments()
        mcd = Mcd(clauses.split("\n"), params)
        params.update(mcd.get_layout_data())
        d = mcd.get_layout_data()
        evaluate = fitness(d["links"], d["multiplicity"], d["col_count"], d["row_count"])
        size = d["col_count"] * d["row_count"]
        (crossing_count, total_distances) = evaluate(list(range(size)))
        self.assertEqual(crossing_count, 0)
        self.assertEqual(total_distances, 0.0)

    def test_optimal_layout_with_reflexive_association(self):
        clauses = """
            Assistas, 01 Hci poilu, 0N Hci poilu
            Hci poilu: graffiti, champignon, troussa, graffiti
            Rayonnait, 0N Hci poilu, 0N Lappa: monobloc

            Brisa: souffrait
            Pillards, 0N Brisa, 0N Lappa, 0N Hci poilu: disions, lascar
            Lappa: graffiti, champignon
            Puni, 11 Lappa, 0N Lappa
        """.replace("  ", "")
        params = parsed_arguments()
        mcd = Mcd(clauses.split("\n"), params)
        params.update(mcd.get_layout_data())
        d = mcd.get_layout_data()
        evaluate = fitness(d["links"], d["multiplicity"], d["col_count"], d["row_count"])
        size = d["col_count"] * d["row_count"]
        (crossing_count, total_distances) = evaluate(list(range(size)))
        self.assertEqual(crossing_count, 0)
        self.assertEqual(total_distances, 0.0)

    def test_diagonal_reflexive_association(self):
        clauses = """
            Norm : Draw, Unit, Folk, Peer, Tour, Hall
            :

            :
            Baby, 1N Norm, 0N> Norm
        """.replace("  ", "")
        params = parsed_arguments()
        mcd = Mcd(clauses.split("\n"), params)
        params.update(mcd.get_layout_data())
        d = mcd.get_layout_data()
        evaluate = fitness(d["links"], d["multiplicity"], d["col_count"], d["row_count"])
        size = d["col_count"] * d["row_count"]
        (crossing_count, total_distances) = evaluate(list(range(size)))
        self.assertEqual(crossing_count, 0)
        self.assertEqual(round(total_distances, 4), 0.8284)

    def test_2_0_link(self):
        clauses = """
            CLIENT: Réf. client, Nom, Prénom, Adresse
            PASSER, 0N CLIENT, 11 COMMANDE
            :
            COMMANDE: Num commande, Date, Montant
        """.replace("  ", "")
        params = parsed_arguments()
        mcd = Mcd(clauses.split("\n"), params)
        params.update(mcd.get_layout_data())
        d = mcd.get_layout_data()
        evaluate = fitness(d["links"], d["multiplicity"], d["col_count"], d["row_count"])
        size = d["col_count"] * d["row_count"]
        (crossing_count, total_distances) = evaluate(list(range(size)))
        self.assertEqual(crossing_count, 0)
        self.assertEqual(total_distances, 1.0)

    def test_1_1_link(self):
        clauses = """
            CLIENT: Réf. client, Nom, Prénom, Adresse
            PASSER, 0N CLIENT, 11 COMMANDE
            
            COMMANDE: Num commande, Date, Montant
            :
        """.replace("  ", "")
        params = parsed_arguments()
        mcd = Mcd(clauses.split("\n"), params)
        params.update(mcd.get_layout_data())
        d = mcd.get_layout_data()
        evaluate = fitness(d["links"], d["multiplicity"], d["col_count"], d["row_count"])
        size = d["col_count"] * d["row_count"]
        (crossing_count, total_distances) = evaluate(list(range(size)))
        self.assertEqual(crossing_count, 0)
        self.assertEqual(total_distances, hypot(1, 1) - 1)

    def test_2_1_link(self):
        clauses = """
            :
            CLIENT: Réf. client, Nom, Prénom, Adresse
            PASSER, 0N CLIENT, 11 COMMANDE
            
            COMMANDE: Num commande, Date, Montant
            :
            :
        """.replace("  ", "")
        params = parsed_arguments()
        mcd = Mcd(clauses.split("\n"), params)
        params.update(mcd.get_layout_data())
        d = mcd.get_layout_data()
        evaluate = fitness(d["links"], d["multiplicity"], d["col_count"], d["row_count"])
        size = d["col_count"] * d["row_count"]
        (crossing_count, total_distances) = evaluate(list(range(size)))
        self.assertEqual(crossing_count, 0)
        self.assertEqual(total_distances, hypot(2, 1) - 1)

    def test_k33(self):
        clauses = """
            DIGNISSIM: nec sem, nunc, vulputate
            IMPERDIET: a praesent, nibh, semper
            TINCIDUNT: faucibus, orci, cursus

            RHONCUS, 1N DIGNISSIM, 1N IMPERDIET, 1N TINCIDUNT
            SODALES, 1N DIGNISSIM, 1N IMPERDIET, 1N TINCIDUNT
            QUIS ENIM, 1N DIGNISSIM, 1N IMPERDIET, 1N TINCIDUNT
        """.replace("  ", "")
        params = parsed_arguments()
        mcd = Mcd(clauses.split("\n"), params)
        params.update(mcd.get_layout_data())
        d = mcd.get_layout_data()
        evaluate = fitness(d["links"], d["multiplicity"], d["col_count"], d["row_count"])
        size = d["col_count"] * d["row_count"]
        (crossing_count, total_distances) = evaluate(list(range(size)))
        self.assertEqual(crossing_count, 9)

    def test_k33_better(self):
        clauses = """
            DIGNISSIM: nec sem, nunc, vulputate
            RHONCUS, 1N DIGNISSIM, 1N IMPERDIET, 1N TINCIDUNT
            IMPERDIET: a praesent, nibh, semper

            SODALES, 1N DIGNISSIM, 1N IMPERDIET, 1N TINCIDUNT
            TINCIDUNT: faucibus, orci, cursus
            QUIS ENIM, 1N DIGNISSIM, 1N IMPERDIET, 1N TINCIDUNT
        """.replace("  ", "")
        params = parsed_arguments()
        mcd = Mcd(clauses.split("\n"), params)
        params.update(mcd.get_layout_data())
        d = mcd.get_layout_data()
        evaluate = fitness(d["links"], d["multiplicity"], d["col_count"], d["row_count"])
        size = d["col_count"] * d["row_count"]
        (crossing_count, total_distances) = evaluate(list(range(size)))
        self.assertEqual(crossing_count, 3)
    

if __name__ == '__main__':
    unittest.main()
